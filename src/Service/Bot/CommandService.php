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
     *
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
            sprintf(ReplyMessages::GREETING."\n\n".ReplyMessages::CATEGORY_QUESTION, $message->from->first_name);

        $keyboard = $this->generateCategoriesInlineKeyboard();

        return $this->bot->sendMessage($message->chat->id, $text, 'HTML', false, null, $keyboard);
    }

    /**
     * @param $message
     *
     * @return Message
     *
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function help($message)
    {
        return $this->bot->sendMessage($message->chat->id, ReplyMessages::HELP, 'HTML');
    }

    /**
     * @param $message
     *
     * @return bool|Message
     *
     * @throws ClientExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function vacancy($message, int $page = 1)
    {
        $telegramChatId = $message->chat->id;

        $chat = $this->em->getRepository(Chat::class)->findOneBy(['chatId' => $telegramChatId]);

        if (!$chat) {
            return $this->setCategory($message);
        }

        $categoryId = $chat->getVacancyCategoryId();

        // Get vacancies from cache by page
        $vacancies = $this->getVacancies($categoryId, $page);

        if (0 == count($vacancies)) {
            return $this->bot->sendMessage($telegramChatId, ReplyMessages::NO_VACANCIES, 'HTML');
        }

        // Get a vacancy
        $vacancy = $vacancies[0];

        // Get the category name from cache
        $categoryName = $this->getCategoryName($vacancy);

        $salary = $vacancy->salary ?: 'Qeyd edilməyib';

        $text =
            sprintf(ReplyMessages::VACANCY, $vacancy->title, $categoryName, $vacancy->company, $salary);

        $keyboard = self::generateVacancyInlineKeyboard($vacancy, $page);

        return $this->bot->sendMessage($telegramChatId, $text, 'HTML', false, null, $keyboard);
    }

    /**
     * @param $message
     *
     * @return Message
     *
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
     *
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function credits($message)
    {
        return $this->bot->sendMessage($message->chat->id, ReplyMessages::CREDITS, 'HTML');
    }

    /**
     * @param $message
     *
     * @return Message
     *
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function donate($message)
    {
        return $this->bot->sendMessage($message->chat->id, sprintf(ReplyMessages::DONATE, $message->from->first_name));
    }

    /**
     * @return mixed
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getVacancies(int $categoryId, int $page)
    {
        return $this->cache->get(
            'app.vacancies.'.$categoryId.'.'.$page,
            function (ItemInterface $item) use ($categoryId, $page) {
                $item->expiresAfter(3600);

                $now = new \DateTime('now', new \DateTimeZone('Asia/Baku'));
                $now->setTimezone(new \DateTimeZone('UTC'));

                $aWeekBefore = new \DateTime('today midnight', new \DateTimeZone('Asia/Baku'));
                $aWeekBefore->setTimezone(new \DateTimeZone('UTC'));
                $aWeekBefore->modify('-1 week');

                if (!$categoryId) {
                    return $this->api->getVacancies(
                        [
                            'itemsPerPage' => 1,
                            'page' => $page,
                            'createdAt[before]' => $now->format('Y-m-d H:i'),
                            'createdAt[after]' => $aWeekBefore->format('Y-m-d H:i'),
                            'order[id]' => 'desc',
                        ]
                    );
                }

                return $this->api->getVacanciesByCategoryId(
                    $categoryId,
                    [
                        'itemsPerPage' => 1,
                        'page' => $page,
                        'createdAt[before]' => $now->format('Y-m-d H:i'),
                        'createdAt[after]' => $aWeekBefore->format('Y-m-d H:i'),
                        'order[id]' => 'desc',
                    ]
                );
            }
        );
    }

    /**
     * @param $vacancy
     *
     * @return mixed
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getCategoryName($vacancy)
    {
        return $this->cache->get(
            'app.category.'.str_replace('/', '', $vacancy->category),
            function (ItemInterface $item) use ($vacancy) {
                $item->expiresAfter(3600 * 24);

                return str_replace(' ', '', ucwords($vacancy->category->name));
            }
        );
    }

    /**
     * @return InlineKeyboardMarkup
     *
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
                        'callback_data' => json_encode(['command' => 'save_user_category', 'id' => $category->id]),
                    ];
            }
            ++$row;
        }

        $buttons[$row][] =
            ['text' => 'Hamısı', 'callback_data' => json_encode(['command' => 'save_user_category', 'id' => null])];

        return new InlineKeyboardMarkup(
            $buttons
        );
    }

    /**
     * @param $vacancy
     *
     * @return InlineKeyboardMarkup
     */
    public static function generateVacancyInlineKeyboard($vacancy, int $page, bool $expanded = false)
    {
        $toggleDetailsButton = [
            'text' => 'Ətraflı',
            'callback_data' => json_encode(['command' => 'read_more', 'id' => $vacancy->id, 'page' => $page]),
        ];

        if ($expanded) {
            $toggleDetailsButton = [
                'text' => 'Bağla',
                'callback_data' => json_encode(['command' => 'read_less', 'id' => $vacancy->id, 'page' => $page]),
            ];
        }

        return new InlineKeyboardMarkup(
            [
                [
                    $toggleDetailsButton,
                ],
                [
                    ['text' => 'Mənbə', 'url' => $vacancy->url],
                ],
                [
                    [
                        'text' => '⬅️ Əvvəlki',
                        'callback_data' => json_encode(['command' => 'prev', 'page' => ($page - 1)]),
                    ],
                    [
                        'text' => 'Növbəti ➡️',
                        'callback_data' => json_encode(['command' => 'next', 'page' => ($page + 1)]),
                    ],
                ],
            ]
        );
    }
}
