<?php

namespace App\Service\Bot;


use App\Entity\Chat;
use App\Service\Api\Vacansee;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use TelegramBot\Api\Exception;
use TelegramBot\Api\InvalidArgumentException;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use TelegramBot\Api\Types\Message;

class CommandService
{
    public const COMMANDS = [
        '/start',
        '/help',
        '/vacancy',
        '/setcategory',
        '/credits',
        '/donate',
    ];

    private Bot $bot;

    private Vacansee $api;

    private CacheInterface $cache;

    private EntityManagerInterface $em;

    public function __construct(Bot $bot, Vacansee $api, CacheInterface $cache, EntityManagerInterface $em)
    {
        $this->bot = $bot;
        $this->api = $api;
        $this->cache = $cache;
        $this->em = $em;
    }

    /**
     * @param $name
     * @param $arguments
     */
    public function __call($name, $arguments)
    {
        $name = ltrim($name, '/');

        call_user_func_array([$this, $name], $arguments);
    }

    /**
     * @param $message
     *
     * @return Message
     * @throws ClientExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function start($message)
    {
        $text =
            sprintf(ReplyMessages::GREETING . "\n\n" . ReplyMessages::CATEGORY_QUESTION, $message->from->first_name);

        $keyboard = $this->generateCategoriesInlineKeyboard();

        return $this->bot->sendMessage($message->chat->id, $text, 'HTML', false, null, $keyboard);
    }

    /**
     * @param $message
     *
     * @return Message
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function help($message)
    {
        return $this->bot->sendMessage($message->chat->id, ReplyMessages::HELP, 'HTML');
    }

    /**
     * @param      $message
     * @param bool $isCallbackQuery
     *
     * @return Message
     * @throws ClientExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function vacancy($message, $isCallbackQuery = false)
    {
        $chatId = $message->chat->id;

        // Get chat from cache
        $chat = $this->cache->get(
            'app.chat.' . $chatId,
            function (ItemInterface $item) use ($chatId) {
                $item->expiresAfter(3600 * 24);

                return $this->em->getRepository(Chat::class)->findOneBy(['chatId' => $chatId]);
            }
        );

        if (!$chat) {
            return $this->setCategory($message);
        }

        $categoryId = $chat->getVacancyCategoryId();

        // Get vacancies from cache
        $vacancies = $this->cache->get(
            'app.vacancies.' . $categoryId,
            function (ItemInterface $item) use ($categoryId) {
                $item->expiresAfter(3600);

                if (!$categoryId) {
                    return $this->api->getVacancies();
                }

                return $this->api->getVacanciesByCategory($categoryId);
            }
        );

        // Get a random vacancy
        $vacancy = $vacancies[mt_rand(0, count($vacancies) - 1)];

        // Get the category name from cache
        $categoryName = $this->cache->get(
            'app.category.' . str_replace('/', '', $vacancy->category),
            function (ItemInterface $item) use ($vacancy) {
                $item->expiresAfter(3600 * 24);

                return str_replace(' ', '', ucwords($this->api->getCategoryByUri($vacancy->category)->name));
            }
        );

        $salary = $vacancy->salary ?: 'Qeyd edilməyib';

        $text =
            sprintf(ReplyMessages::VACANCY, $vacancy->title, $categoryName, $vacancy->company, $salary);

        $keyboard = self::generateVacancyListInlineKeyboard($vacancy);

        // Update the message with new vacancy if it was a callback query
        if ($isCallbackQuery) {
            return $this->bot->editMessageText(
                $message->chat->id,
                $message->message_id,
                $text,
                'HTML',
                false,
                $keyboard
            );
        }

        return $this->bot->sendMessage($message->chat->id, $text, 'HTML', false, null, $keyboard);
    }

    /**
     * @param $message
     *
     * @return Message
     * @throws ClientExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function setCategory($message)
    {
        $text = sprintf(ReplyMessages::CATEGORY_QUESTION);

        $keyboard = $this->generateCategoriesInlineKeyboard();

        return $this->bot->sendMessage($message->chat->id, $text, 'HTML', false, null, $keyboard);
    }

    /**
     * @param $message
     *
     * @return Message
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function credits($message)
    {
        // todo
        return $this->bot->sendMessage($message->chat->id, 'Hələ ki, bu komandanı başa düşmürəm... Amma öyrənirəm.');
    }

    /**
     * @param $message
     *
     * @return Message
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function donate($message)
    {
        // todo
        return $this->bot->sendMessage($message->chat->id, 'Hələ ki, bu komandanı başa düşmürəm... Amma öyrənirəm.');
    }

    /**
     * @return InlineKeyboardMarkup
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    private function generateCategoriesInlineKeyboard()
    {
        $categories = array_chunk($this->api->getCategories(), 3, true);

        $buttons = [];

        $row = 0;
        foreach ($categories as $chunk) {
            foreach ($chunk as $category) {
                $buttons[$row][] =
                    [
                        'text' => $category->name,
                        'callback_data' => json_encode(['command' => 'set_category', 'id' => $category->id])
                    ];
            }
            $row++;
        }

        $buttons[$row][] =
            ['text' => 'Hamısı', 'callback_data' => json_encode(['command' => 'set_category', 'id' => null])];

        return new InlineKeyboardMarkup(
            $buttons
        );
    }

    /**
     * @param $vacancy
     *
     * @return InlineKeyboardMarkup
     */
    public static function generateVacancyListInlineKeyboard($vacancy)
    {
        return new InlineKeyboardMarkup(
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
                        'callback_data' => json_encode(['command' => 'get_another'])
                    ],
                ]
            ]
        );
    }
}