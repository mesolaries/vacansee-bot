<?php

namespace App\Service;


class ReplyMessages
{
    public const GREETING = "Hello, %s. I'll help you to find a job!\nSend me /vacancy to get started or /help to get a list of all commands.";

    public const VACANCY = "#%s Vacancy!\n\nTitle: %s\nCompany: %s\nSalary: %s";

    public const VACANCY_DESCRIPTION = "\nDescription:\n\n%s";
}