<?php

namespace Modules\Notices\Support;

use Modules\Core\Support\SafeHtml as CoreSafeHtml;

/**
 * Kept so Notices code and callers keep working; the sanitiser itself now lives in Core where Website shares it.
 */
final class SafeHtml extends CoreSafeHtml {}
