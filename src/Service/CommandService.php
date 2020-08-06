<?php

namespace App\Service;


use App\Service\Api\Vacansee;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;

class CommandService
{
    public const COMMANDS = [
        '/start',
        '/help',
        '/vacancy',
        '/credits',
        '/donate',
    ];

    private Bot $bot;

    private Vacansee $api;

    public function __construct(Bot $bot, Vacansee $api)
    {
        $this->bot = $bot;
        $this->api = $api;
    }

    public function __call($name, $arguments)
    {
        $name = ltrim($name, '/');

        call_user_func_array([$this, $name], $arguments);
    }

    public function start($message)
    {
        // todo
        $this->bot->sendMessage($message->chat->id, sprintf(ReplyMessages::GREETING, $message->from->first_name));
    }

    public function help($message)
    {
        // todo
        $this->bot->sendMessage($message->chat->id, 'This is a help message');
    }

    public function vacancy($message)
    {
        $vacancies = $this->api->getVacancies();

        $vacancy = $vacancies[mt_rand(0, count($vacancies) - 1)];

        $text =
            sprintf(ReplyMessages::VACANCY, $vacancy->category, $vacancy->title, $vacancy->company, $vacancy->salary);
        $keyboard = new InlineKeyboardMarkup(
            [
                [
                    [
                        'text' => "Read more",
                        'callback_data' => json_encode(['command' => 'readmore', 'id' => $vacancy->id])
                    ],
                    ['text' => "Link", 'url' => $vacancy->url]
                ]
            ]
        );
        $this->bot->sendMessage($message->chat->id, $text, null, false, null, $keyboard);
    }

    public function credits($message)
    {
        // todo
        $this->bot->sendMessage($message->chat->id, 'Here will be credits and other info about contributor');
    }

    public function donate($message)
    {
        // todo
        $this->bot->sendMessage($message->chat->id, 'It will give a user link where he can donate');
    }
}