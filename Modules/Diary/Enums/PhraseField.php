<?php

namespace Modules\Diary\Enums;

/**
 * Which input a quick phrase is offered for.
 */
enum PhraseField: string
{
    case Lesson = 'lesson';
    case Homework = 'homework';
    case Note = 'note';
    case Remark = 'remark';
}
