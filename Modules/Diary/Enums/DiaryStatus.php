<?php

namespace Modules\Diary\Enums;

enum DiaryStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}
