<?php

namespace App\Service\Bot;


class ReplyMessages
{
    public const GREETING = "Salam, %s. Mən sənə iş tapmağa kömək edəcəm!";

    public const CATEGORY_QUESTION = "Səni hansı kateqoriyadan olan işlər maraqlandırır?";

    public const VACANCY = "<b>%s</b>\n#%s\n\n<b>Şirkət:</b> %s\n<b>Əmək haqqı:</b> %s";

    public const VACANCY_DESCRIPTION = "\n<b>Ətraflı:</b>\n\n%s";

    public const DONT_UNDERSTAND = "Bağışla... Amma mən səni başa düşmədim.";

    public const NOT_EXISTING_CALLBACK = "Belə düymə yoxdur və ya köhnəlib. Yenidən /vacancy göndərməyi yoxla.";

    public const CATEGORY_WAS_SET = "Oldu. Vakansiya göndərməyimi istəyəndə mənə /vacancy yaz.";

    public const NO_VACANCIES = "Hal-hazırda vakansiya yoxdur. Bir qədər sonra yenidən yoxla.";

    public const NO_VACANCY_PAGINATION = "Vakansiya yoxdur.";

    public const DONATE = "%s, mənə dəstək olmaq üçün aşağıdakı linkə keçid et və istədiyin məbləği göndər.\n\n" .
    "https://yasobe.ru/na/donate_vacansee\n\n" .
    "Dəstəyin üçün təşəkkür edirəm!";

    public const CREDITS = "Emil Manafov (@mnf_emil) tərəfindən yaradılıb.\n" .
    "Məqsəd, insanların iş axtarmaq çətinliklərini az da olsa aradan qaldırmaqdır.\n\n" .
    "<b>GitHub:</b> https://github.com/mesolaries/vacansee-bot\n" .
    "<b>Telegram kanal:</b> @vacansee_all\n\n" .
    "<b>Dəstək olmaq üçün:</b> /donate";

    public const HELP = "Mən bu komandaları başa düşürəm:\n\n\n" .
    "/start - Məni ilk dəfə görürsənsə burdan başla\n\n" .
    "/help - Bu yazını göstərmək üçün\n\n" .
    "/vacancy - Son həftə ərazində paylaşılan vakansiyaları göndərəcəm\n\n" .
    "/setcategory - Vakansiya kateqoriyasını dəyişmək üçün\n\n" .
    "/credits - Mənim yaradıcılarım haqqında məlumat verəcəm\n\n" .
    "/donate - Xərclərimi ödəmək üçün proqramçının dəstəyə ehtiyacı var.\n\n" .
    "Bir təşəkkür <i>kofesi ☕️</i> bəsdir &#x1F60A";
}