<?php

namespace App\Service;


use App\Entity\Bot\Chat;
use App\Service\Api\Vacansee;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;

class CallbackService
{
    public const CALLBACKS = [
        'read_more',
        'read_less',
        'get_another',
        'set_category',
    ];

    private Bot $bot;

    private Vacansee $api;

    private CacheInterface $cache;

    private CommandService $botCommand;

    /**
     * @var EntityManagerInterface Entity Manager for default (bot) connection
     */
    private EntityManagerInterface $em;

    public function __construct(
        Bot $bot,
        Vacansee $api,
        CacheInterface $cache,
        EntityManagerInterface $em,
        CommandService $botCommand
    ) {
        $this->bot = $bot;
        $this->api = $api;
        $this->cache = $cache;
        $this->em = $em;
        $this->botCommand = $botCommand;
    }

    public function __call($name, $arguments)
    {
        $name = str_replace(' ', '', lcfirst(ucwords(preg_replace('/[^a-zA-Z0-9]+/', ' ', $name))));

        call_user_func_array([$this, $name], $arguments);
    }


    public function readMore($query, $message)
    {
        $id = (int)$query->id;

        // Caching a vacancy
        $vacancy = $this->cache->get(
            "app.vacancy$id",
            function (ItemInterface $item) use ($id) {
                $item->expiresAfter(3600);

                return $this->api->getVacancy($id);
            }
        );

        $salary = $vacancy->salary ?: 'Qeyd edilməyib';

        $category = $this->api->getCategoryByUri($vacancy->category);

        $categoryName = str_replace(' ', '', ucwords($category->name));

        $categoryId = $category->id;


        $keyboard = new InlineKeyboardMarkup(
            [
                [
                    [
                        'text' => "Gizlət",
                        'callback_data' => json_encode(['command' => 'read_less', 'id' => $vacancy->id])
                    ],
                    ['text' => "Mənbə", 'url' => $vacancy->url],
                ],
                [
                    [
                        'text' => "Başqasını göstər",
                        'callback_data' => json_encode(['command' => 'get_another', 'categoryId' => $categoryId])
                    ],
                ]
            ]
        );

        $vacancy_description =
            trim(
                strip_tags(
                    preg_replace("/<br ?\\/?>|<\\/?p ?>|<\\/?h[1-6] ?>/", "\n", $vacancy->descriptionHtml),
                    ['b', 'strong', 'i', 'em', 'u', 'ins', 's', 'strike', 'del', 'a', 'code', 'pre']
                )
            );

        $text =
            sprintf(
                ReplyMessages::VACANCY . ReplyMessages::VACANCY_DESCRIPTION,
                $vacancy->title,
                $categoryName,
                $vacancy->company,
                $salary,
                $vacancy_description
            );

        $this->bot->editMessageText(
            $message->chat->id,
            $message->message_id,
            $text,
            'HTML',
            false,
            $keyboard
        );
    }

    public function readLess($query, $message)
    {
        $id = (int)$query->id;

        // Caching a vacancy
        $vacancy = $this->cache->get(
            "app.vacancy$id",
            function (ItemInterface $item) use ($id) {
                $item->expiresAfter(3600);

                return $this->api->getVacancy($id);
            }
        );

        $salary = $vacancy->salary ?: 'Qeyd edilməyib';

        $category = $this->api->getCategoryByUri($vacancy->category);

        $categoryName = str_replace(' ', '', ucwords($category->name));

        $categoryId = $category->id;

        $keyboard = new InlineKeyboardMarkup(
            [
                [
                    [
                        'text' => "Ətraflı",
                        'callback_data' => json_encode(['command' => 'read_more', 'id' => $vacancy->id])
                    ],
                    ['text' => "Mənbə", 'url' => $vacancy->url],
                ],
                [
                    [
                        'text' => "Başqasını göstər",
                        'callback_data' => json_encode(['command' => 'get_another', 'categoryId' => $categoryId])
                    ],
                ]
            ]
        );

        $text =
            sprintf(ReplyMessages::VACANCY, $vacancy->title, $categoryName, $vacancy->company, $salary);

        $this->bot->editMessageText(
            $message->chat->id,
            $message->message_id,
            $text,
            'HTML',
            false,
            $keyboard
        );
    }

    public function getAnother($query, $message)
    {
        return $this->botCommand->vacancy($message, true);
    }

    public function setCategory($query, $message)
    {
        $categoryId = (int)$query->id;

        $repository = $this->em->getRepository(Chat::class);

        $chat = $repository->findOneBy(['chatId' => $message->chat->id]) ?? new Chat();

        $chat->setTitle(@$message->chat->title);
        $chat->setUsername(@$message->chat->username);
        $chat->setFirstName(@$message->chat->first_name);
        $chat->setType($message->chat->type);
        $chat->setChatId($message->chat->id);
        $chat->setVacancyCategoryId($categoryId);

        $this->em->persist($chat);
        $this->em->flush();

        $this->bot->sendMessage($message->chat->id, ReplyMessages::CATEGORY_WAS_SET);
    }
}