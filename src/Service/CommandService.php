<?php

namespace App\Service;


use App\Service\Api\Vacansee;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;

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

    public function __construct(Bot $bot, Vacansee $api, CacheInterface $cache)
    {
        $this->bot = $bot;
        $this->api = $api;
        $this->cache = $cache;
    }

    public function __call($name, $arguments)
    {
        $name = ltrim($name, '/');

        call_user_func_array([$this, $name], $arguments);
    }

    public function start($message)
    {
        // todo
        $text = sprintf(ReplyMessages::GREETING . "\n\n" . ReplyMessages::CATEGORY_QUESTION, $message->from->first_name);

        $keyboard = $this->getCategoriesInlineKeyboard();

        $this->bot->sendMessage($message->chat->id, $text, 'HTML', false, null, $keyboard);
    }

    public function help($message)
    {
        // todo
        $this->bot->sendMessage($message->chat->id, ReplyMessages::HELP, 'HTML');
    }

    public function vacancy($message)
    {
        // Caching vacancies
        $vacancies = $this->cache->get(
            'app.vacancies',
            function (ItemInterface $item) {
                $item->expiresAfter(3600);

                return $this->api->getVacancies();
            }
        );

        $vacancy = $vacancies[mt_rand(0, count($vacancies) - 1)];

        $text =
            sprintf(ReplyMessages::VACANCY, $vacancy->title, $vacancy->category, $vacancy->company, $vacancy->salary);
        $keyboard = new InlineKeyboardMarkup(
            [
                [
                    [
                        'text' => "Ətrafı",
                        'callback_data' => json_encode(['command' => 'read_more', 'id' => $vacancy->id])
                    ],
                    ['text' => "Mənbə", 'url' => $vacancy->url],
                ],
                [
                    ['text' => "Başqasını göstər", 'callback_data' => json_encode(['command' => 'get_another'])],
                ]
            ]
        );
        $this->bot->sendMessage($message->chat->id, $text, 'HTML', false, null, $keyboard);
    }

    public function setCategory($message)
    {
        $text = sprintf(ReplyMessages::CATEGORY_QUESTION);

        $keyboard = $this->getCategoriesInlineKeyboard();

        $this->bot->sendMessage($message->chat->id, $text, 'HTML', false, null, $keyboard);
    }

    public function credits($message)
    {
        // todo
        $this->bot->sendMessage($message->chat->id, 'Hələ ki, bu komandanı başa düşmürəm... Amma öyrənirəm.');
    }

    public function donate($message)
    {
        // todo
        $this->bot->sendMessage($message->chat->id, 'Hələ ki, bu komandanı başa düşmürəm... Amma öyrənirəm.');
    }

    private function getCategoriesInlineKeyboard()
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
}