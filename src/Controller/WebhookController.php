<?php

namespace App\Controller;

use App\Service\Bot;
use App\Service\CallbackService;
use App\Service\CommandService;
use App\Service\ReplyMessages;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use TelegramBot\Api\Exception;
use TelegramBot\Api\InvalidArgumentException;

class WebhookController extends AbstractController
{
    private Bot $bot;

    private CommandService $command;

    private CallbackService $callback;

    /**
     * WebhookController constructor.
     *
     * @param Bot            $bot
     * @param CommandService $command
     */
    public function __construct(Bot $bot, CommandService $command, CallbackService $callback)
    {
        $this->bot = $bot;
        $this->command = $command;
        $this->callback = $callback;
    }

    /**
     * @Route("/bot/{token}", name="app.bot.webhook", methods={"POST"}, requirements={"token"="\d+:.+"})
     *
     * @param Request $request
     * @param string  $token
     *
     * @return Response
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function webhook(Request $request, string $token)
    {
        if ($token !== $this->getParameter('telegram_bot_token')) {
            return new Response(null, Response::HTTP_FORBIDDEN);
        }

        $content = json_decode($request->getContent());

        if (isset($content->message) || isset($content->edited_message)) {
            $message = @$content->message ?: $content->edited_message;

            // Remove everything after @ from message text (if exists). Return empty string if there's no message text at all
            $text = @strtok($message->text, '@') ?: '';

            if (!in_array($text, $this->command::COMMANDS)) {
                $this->bot->sendMessage($message->chat->id, ReplyMessages::DONT_UNDERSTAND);
                return new Response();
            }

            $this->command->$text($message);
        } elseif (isset($content->callback_query)) {
            $message = $content->callback_query->message;
            $query = json_decode($content->callback_query->data);
            $text = $query->command;

            if (in_array($text, $this->callback::CALLBACKS)) {
                $this->callback->$text($query, $message);
            }
        }

        return new Response();
    }
}
