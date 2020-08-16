<?php

namespace App\Service\Bot;


use App\Entity\Chat;
use App\Service\Api\Vacansee;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use TelegramBot\Api\Exception;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use TelegramBot\Api\Types\Message;

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

    /**
     * @param $name
     * @param $arguments
     */
    public function __call($name, $arguments)
    {
        $name = str_replace(' ', '', lcfirst(ucwords(preg_replace('/[^a-zA-Z0-9]+/', ' ', $name))));

        call_user_func_array([$this, $name], $arguments);
    }


    /**
     * @param $query
     * @param $message
     *
     * @return Message
     * @throws InvalidArgumentException
     */
    public function readMore($query, $message)
    {
        return $this->updateVacancyText($query, $message, true);
    }

    /**
     * @param $query
     * @param $message
     *
     * @return Message
     * @throws InvalidArgumentException
     */
    public function readLess($query, $message)
    {
        return $this->updateVacancyText($query, $message);
    }

    /**
     * @param $query
     * @param $message
     *
     * @return Message
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws \TelegramBot\Api\InvalidArgumentException
     */
    public function getAnother($query, $message)
    {
        return $this->botCommand->vacancy($message, true);
    }

    /**
     * @param $query
     * @param $message
     *
     * @return Message
     * @throws InvalidArgumentException
     * @throws Exception
     * @throws \TelegramBot\Api\InvalidArgumentException
     */
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

        $this->cache->delete('app.chat.' . $message->chat->id);

        return $this->bot->sendMessage($message->chat->id, ReplyMessages::CATEGORY_WAS_SET);
    }

    /**
     * @param      $query
     * @param      $message
     * @param bool $expand
     *
     * @return Message
     * @throws InvalidArgumentException
     */
    private function updateVacancyText($query, $message, $expand = false)
    {
        $id = (int)$query->id;

        // Get the vacancy from cache
        $vacancy = $this->cache->get(
            'app.vacancy.' . $id,
            function (ItemInterface $item) use ($id) {
                $item->expiresAfter(3600 * 24);

                return $this->api->getVacancy($id);
            }
        );

        $salary = $vacancy->salary ?: 'Qeyd edilməyib';

        // Get the category name from cache
        $categoryName = $this->cache->get(
            'app.category.' . str_replace('/', '', $vacancy->category),
            function (ItemInterface $item) use ($vacancy) {
                $item->expiresAfter(3600 * 24);

                return str_replace(' ', '', ucwords($this->api->getCategoryByUri($vacancy->category)->name));
            }
        );

        if ($expand) {
            $keyboard = self::generateVacancyDetailInlineKeyboard($vacancy);

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
        } else {
            $keyboard = $this->botCommand::generateVacancyListInlineKeyboard($vacancy);

            $text =
                sprintf(ReplyMessages::VACANCY, $vacancy->title, $categoryName, $vacancy->company, $salary);
        }

        return $this->bot->editMessageText(
            $message->chat->id,
            $message->message_id,
            $text,
            'HTML',
            false,
            $keyboard
        );
    }

    /**
     * @param $vacancy
     *
     * @return InlineKeyboardMarkup
     */
    private static function generateVacancyDetailInlineKeyboard($vacancy)
    {
        return new InlineKeyboardMarkup(
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
                        'callback_data' => json_encode(['command' => 'get_another'])
                    ],
                ]
            ]
        );
    }
}