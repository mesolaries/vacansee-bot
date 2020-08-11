<?php

namespace App\Service;


class ReplyMessages
{
    public const GREETING = "Salam, %s. Mən sənə iş tapmağa kömək edəcəm!";

    public const CATEGORY_QUESTION = "Səni hansı kateqoriyadan olan işlər maraqlandırır?";

    public const VACANCY = "<b>%s</b>\n#%s\n\n<b>Şirkət:</b> %s\n<b>Əmək haqqı:</b> %s";

    public const VACANCY_DESCRIPTION = "\n<b>Ətraflı:</b>\n\n%s";

    public const DONT_UNDERSTAND = "Bağışla... Amma mən səni başa düşmədim.";

    public const CATEGORY_WAS_SET = "Oldu. Vakansiya göndərməyimi istəyəndə mənə /vacancy yaz.";

    public const HELP = "Mən bu komandaları başa düşürəm:\n\n\n" .
    "/start - Məni ilk dəfə görürsənsə burdan başla\n\n" .
    "/help - Bu yazını göstərmək üçün\n\n" .
    "/vacancy - Ən son 30 vakansiyadan birini seçib göstərirəm\n\n" .
    "/credits - Mənim yaradıcılarım haqqında məlumat verəcəm\n\n" .
    "/donate - Xərclərimi ödəmək üçün proqramçının dəstəyə ehtiyacı var.\n\n" .
    "Bir <b>çox sağ ol</b> <i>kofesi</i> bəsdir &#x1F60A";
}