<?php

namespace App\Service;


use App\Service\Api\Vacansee;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;

class CallbackService
{
    public const CALLBACKS = [
        'read_more',
        'read_less',
        'get_another',
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
                    ['text' => "Başqasını göstər", 'callback_data' => json_encode(['command' => 'get_another'])],
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
                $vacancy->category,
                $vacancy->company,
                $vacancy->salary,
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
                    ['text' => "Başqasını göstər", 'callback_data' => json_encode(['command' => 'get_another'])],
                ]
            ]
        );

        $text =
            sprintf(ReplyMessages::VACANCY, $vacancy->title, $vacancy->category, $vacancy->company, $vacancy->salary);

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
                        'text' => "Ətraflı",
                        'callback_data' => json_encode(['command' => 'read_more', 'id' => $vacancy->id])
                    ],
                    ['text' => "Mənbə", 'url' => $vacancy->url],
                ],
                [
                    ['text' => "Başqasını göstər", 'callback_data' => json_encode(['command' => 'get_another'])],
                ]
            ]
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
}