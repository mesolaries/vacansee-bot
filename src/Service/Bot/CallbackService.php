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
use TelegramBot\Api\HttpException;
use TelegramBot\Api\Types\Message;

class CallbackService
{
    public const CALLBACKS = [
        'read_more',
        'read_less',
        'save_user_category',
        'next',
        'prev',
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
     * @param $callbackQueryId
     *
     * @return Message
     *
     * @throws InvalidArgumentException
     */
    public function readMore($query, $message, $callbackQueryId)
    {
        $this->answerCallbackQuery($callbackQueryId);

        return $this->toggleVacancyText($query, $message, $callbackQueryId, true);
    }

    /**
     * @param $query
     * @param $message
     * @param $callbackQueryId
     *
     * @return Message
     *
     * @throws InvalidArgumentException
     */
    public function readLess($query, $message, $callbackQueryId)
    {
        $this->answerCallbackQuery($callbackQueryId);

        return $this->toggleVacancyText($query, $message, $callbackQueryId, false);
    }

    /**
     * @param $query
     * @param $message
     * @param $callbackQueryId
     *
     * @return bool|Message
     *
     * @throws ClientExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws \TelegramBot\Api\InvalidArgumentException
     */
    public function next($query, $message, $callbackQueryId)
    {
        $page = (int) $query->page;

        return $this->changeVacancy($message, $callbackQueryId, $page);
    }

    /**
     * @param $query
     * @param $message
     * @param $callbackQueryId
     *
     * @return bool|Message
     *
     * @throws ClientExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws \TelegramBot\Api\InvalidArgumentException
     */
    public function prev($query, $message, $callbackQueryId)
    {
        $page = (int) $query->page;

        if ($page < 1) {
            return $this->answerCallbackQuery($callbackQueryId, ReplyMessages::NO_VACANCY_PAGINATION);
        }

        return $this->changeVacancy($message, $callbackQueryId, $page);
    }

    /**
     * @param $query
     * @param $message
     * @param $callbackQueryId
     *
     * @return Message
     *
     * @throws InvalidArgumentException
     */
    public function saveUserCategory($query, $message, $callbackQueryId)
    {
        $categoryId = (int) $query->id;

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

        $this->cache->delete('app.chat.'.$message->chat->id);

        $this->answerCallbackQuery($callbackQueryId, 'Kateqoriya seçildi.');

        return $this->bot->editMessageText(
            $message->chat->id,
            $message->message_id,
            ReplyMessages::CATEGORY_WAS_SET,
            'HTML'
        );
    }

    /**
     * @param      $query
     * @param      $message
     * @param      $callbackQueryId
     * @param bool $expand
     *
     * @return Message
     *
     * @throws InvalidArgumentException
     */
    private function toggleVacancyText($query, $message, $callbackQueryId, $expand = false)
    {
        $id = (int) $query->id;
        $page = (int) $query->page;

        // Get the vacancy from cache
        $vacancy = $this->cache->get(
            'app.vacancy.'.$id,
            function (ItemInterface $item) use ($id) {
                $item->expiresAfter(3600 * 24);

                return $this->api->getVacancy($id);
            }
        );

        $salary = $vacancy->salary ?: 'Qeyd edilməyib';

        // Get the category name from cache
        $categoryName = $this->botCommand->getCategoryName($vacancy);

        $keyboard = $this->botCommand::generateVacancyInlineKeyboard($vacancy, $page, $expand);

        $text =
            sprintf(ReplyMessages::VACANCY, $vacancy->title, $categoryName, $vacancy->company, $salary);

        if ($expand) {
            $vacancy_description =
                trim(
                    strip_tags(
                        preg_replace('/<br ?\\/?>|<\\/?p ?>|<\\/?li ?>|<\\/?ul ?>|<\\/?h[1-6] ?>/', "\n", $vacancy->descriptionHtml),
                        ['b', 'strong', 'i', 'em', 'u', 'ins', 's', 'strike', 'del', 'a', 'code', 'pre']
                    )
                );

            $text =
                sprintf(
                    ReplyMessages::VACANCY.ReplyMessages::VACANCY_DESCRIPTION,
                    $vacancy->title,
                    $categoryName,
                    $vacancy->company,
                    $salary,
                    $vacancy_description
                );
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
     * @param     $message
     * @param     $callbackQueryId
     * @param int $page
     *
     * @return bool|Message
     *
     * @throws ClientExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws \TelegramBot\Api\InvalidArgumentException
     */
    private function changeVacancy($message, $callbackQueryId, $page = 1)
    {
        $telegramChatId = $message->chat->id;

        $chat = $this->em->getRepository(Chat::class)->findOneBy(['chatId' => $telegramChatId]);

        if (!$chat) {
            return $this->botCommand->setCategory($message);
        }

        $categoryId = $chat->getVacancyCategoryId();

        // Get vacancies from cache by page
        $vacancies = $this->botCommand->getVacancies($categoryId, $page);

        if (0 == count($vacancies)) {
            return $this->answerCallbackQuery($callbackQueryId, ReplyMessages::NO_VACANCY_PAGINATION);
        }

        // Get a vacancy
        $vacancy = $vacancies[0];

        // Get the category name from cache
        $categoryName = $this->botCommand->getCategoryName($vacancy);

        $salary = $vacancy->salary ?: 'Qeyd edilməyib';

        $text =
            sprintf(ReplyMessages::VACANCY, $vacancy->title, $categoryName, $vacancy->company, $salary);

        $keyboard = $this->botCommand::generateVacancyInlineKeyboard($vacancy, $page);

        $this->answerCallbackQuery($callbackQueryId);

        return $this->bot->editMessageText(
            $telegramChatId,
            $message->message_id,
            $text,
            'HTML',
            false,
            $keyboard
        );
    }

    private function answerCallbackQuery($callbackQueryId, $text = null, $showAlert = false)
    {
        try {
            $this->bot->answerCallbackQuery($callbackQueryId, $text, $showAlert);
        } catch (HttpException $e) {
            // Prevent Exception if the script took a long time
        }

        return true;
    }
}
