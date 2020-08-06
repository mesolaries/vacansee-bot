<?php

namespace App\Service;


use App\Service\Api\Vacansee;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;

class CallbackService
{
    public const CALLBACKS = [
        'readmore',
        'readless',
    ];

    private Bot $bot;

    private Vacansee $api;

    private HtmlConverter $converter;

    public function __construct(Bot $bot, Vacansee $api, HtmlConverter $converter)
    {
        $this->bot = $bot;
        $this->api = $api;
        $this->converter = $converter;
    }

    public function __call($name, $arguments)
    {
        $name = str_replace(' ', '', lcfirst(ucwords(preg_replace('/[^a-zA-Z0-9]+/', ' ', $name))));

        call_user_func_array([$this, $name], $arguments);
    }


    public function readMore($query, $message)
    {
        $old_message = $message->text;

        $id = (int)$query->id;

        $vacancy = $this->api->getVacancy($id);

        $keyboard = new InlineKeyboardMarkup(
            [
                [
                    [
                        'text' => "Read less",
                        'callback_data' => json_encode(['command' => 'readless', 'id' => $vacancy->id])
                    ],
                    ['text' => "Link", 'url' => $vacancy->url]
                ]
            ]
        );

        $vacancy_description = $this->converter->convert(nl2br($vacancy->descriptionHtml));

        $new_message = sprintf(ReplyMessages::VACANCY_DESCRIPTION, $vacancy_description);

        $text = $old_message . $new_message;

        $this->bot->editMessageText(
            $message->chat->id,
            $message->message_id,
            $text,
            'Markdown',
            false,
            $keyboard
        );
    }

    public function readLess($query, $message)
    {
        $id = (int)$query->id;

        $vacancy = $this->api->getVacancy($id);

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

        $text =
            sprintf(ReplyMessages::VACANCY, $vacancy->category, $vacancy->title, $vacancy->company, $vacancy->salary);

        $this->bot->editMessageText(
            $message->chat->id,
            $message->message_id,
            $text,
            'Markdown',
            false,
            $keyboard
        );
    }
}