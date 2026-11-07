<?php
/**
 * @var string      $type     'activity' or 'app'
 * @var string[]    $dates    Available dates (Y-m-d), newest first
 * @var string      $date     Selected date
 * @var string      $filter   Selected level / category ('' = all)
 * @var string      $query    Search text
 * @var ?array      $result   Result of LogReader::read() or null
 *
 * All fixed texts come from resources/lang/<code>.php (keys "logs.*").
 */
use App\Core\Activity;
use App\Core\Lang;
use App\Core\LogReader;

$h = static fn($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
// Escaped text from the language file.
$t = static fn(string $key, array $replace = []): string => $h(Lang::get($key, $replace));

$isActivity = $type === 'activity';
$filterOpts = $isActivity ? Activity::categories() : LogReader::levels();

// Builds a URL to this page that keeps the current filters.
$params = ['type' => $type, 'date' => $date, 'f' => $filter, 'q' => $query];
$link = static function (array $override = []) use ($params): string {
    $merged = array_filter(
        array_merge($params, $override),
        static fn($v) => $v !== '' && $v !== null
    );
    return base_url('/admin/logs') . ($merged ? '?' . http_build_query($merged) : '');
};

// Turns the data array into a readable "key: value" list, in the chosen language.
// Keys and known values (status, reason, role, yes/no) are translated; older log
// lines that were stored with already translated text are shown as they were stored.
$formatData = static function (array $data) use ($h): string {
    $translate = static function (string $key, mixed $value): string {
        if (is_bool($value)) {
            return Lang::get($value ? 'common.yes' : 'common.no');
        }
        if (is_array($value)) {
            return (string) json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        $value  = (string) $value;
        $lookup = match ($key) {
            'to'         => 'status.' . $value,
            'reason'     => 'logs.reasons.' . $value,
            'new_role'   => 'roles.' . strtolower($value),
            default      => null,
        };
        return ($lookup !== null && Lang::has($lookup)) ? Lang::get($lookup) : $value;
    };

    $parts = [];
    foreach ($data as $key => $value) {
        $key   = (string) $key;
        $label = Lang::has('logs.data.' . $key) ? Lang::get('logs.data.' . $key) : str_replace('_', ' ', $key);
        $parts[] = '<span class="log-kv"><span class="log-kv__key">' . $h($label)
                 . '</span> ' . $h($translate($key, $value)) . '</span>';
    }
    return implode(' ', $parts);
};
?>

<section class="admin">

    <header class="admin__head">
        <div>
            <h1 class="admin__title"><?= $t('logs.title') ?></h1>
            <p class="admin__subtitle"><?= $t('logs.subtitle', ['days' => (int) env('LOG_RETENTION_DAYS', 30)]) ?></p>
        </div>
        <div class="admin__actions">
            <a class="btn btn--ghost" href="<?= base_url('/admin') ?>"><?= $t('logs.back') ?></a>
        </div>
    </header>

    <!-- Type tabs -->
    <div class="admin-tabs">
        <?php foreach (LogReader::types() as $key => $name): ?>
            <a class="admin-tab <?= $key === $type ? 'admin-tab--active' : '' ?>"
               href="<?= $h(base_url('/admin/logs?type=' . $key)) ?>"><?= $h($name) ?></a>
        <?php endforeach; ?>
    </div>

    <?php if (!$dates): ?>
        <div class="log-empty">
            <?= $t($isActivity ? 'logs.empty_activity' : 'logs.empty_app') ?>
        </div>
    <?php else: ?>

        <!-- Filters -->
        <form class="log-filters" method="get" action="<?= base_url('/admin/logs') ?>">
            <input type="hidden" name="type" value="<?= $h($type) ?>">

            <label class="log-filters__field">
                <span><?= $t('logs.filter.date') ?></span>
                <select name="date" class="select-inline">
                    <?php foreach ($dates as $d): ?>
                        <option value="<?= $h($d) ?>" <?= $d === $date ? 'selected' : '' ?>>
                            <?= $h(Lang::dateShort($d)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="log-filters__field">
                <span><?= $t($isActivity ? 'logs.filter.category' : 'logs.filter.level') ?></span>
                <select name="f" class="select-inline">
                    <option value=""><?= $t('logs.filter.all') ?></option>
                    <?php foreach ($filterOpts as $key => $name): ?>
                        <option value="<?= $h($key) ?>" <?= $key === $filter ? 'selected' : '' ?>><?= $h($name) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="log-filters__field log-filters__field--grow">
                <span><?= $t('logs.filter.search') ?></span>
                <input type="search" name="q" value="<?= $h($query) ?>" maxlength="100"
                       class="log-filters__input"
                       placeholder="<?= $t($isActivity ? 'logs.placeholder.activity' : 'logs.placeholder.app') ?>">
            </label>

            <div class="log-filters__buttons">
                <button type="submit" class="btn btn--sm btn--success"><?= $t('logs.filter.apply') ?></button>
                <a class="btn btn--sm btn--ghost" href="<?= $h(base_url('/admin/logs?type=' . $type)) ?>"><?= $t('logs.filter.reset') ?></a>
            </div>
        </form>

        <?php if ($result === null): ?>
            <div class="log-empty"><?= $t('logs.unreadable') ?></div>
        <?php else: ?>

            <!-- Summary -->
            <div class="log-summary">
                <span><strong><?= $h(Lang::choice('logs.lines', (int) $result['total'])) ?></strong>
                    <?= ($filter !== '' || $query !== '') ? $t('logs.filtered') : '' ?></span>
                <?php foreach ($filterOpts as $key => $name): ?>
                    <?php if (!empty($result['counts'][$key])): ?>
                        <a class="log-chip log-chip--<?= $h($key) ?> <?= $key === $filter ? 'log-chip--active' : '' ?>"
                           href="<?= $h($link(['f' => $key, 'page' => null])) ?>">
                            <?= $h($name) ?> <strong><?= (int) $result['counts'][$key] ?></strong>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
                <span class="log-summary__size"><?= $h(Lang::number($result['size'] / 1024, 1)) ?> KB</span>
            </div>

            <?php if ($result['truncated']): ?>
                <div class="alert alert--error"><?= $t('logs.truncated') ?></div>
            <?php endif; ?>

            <?php if (!$result['entries']): ?>
                <div class="log-empty"><?= $t('logs.no_results') ?></div>
            <?php elseif ($isActivity): ?>

                <div class="log-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th><?= $t('logs.th.time') ?></th>
                            <th><?= $t('logs.th.user') ?></th>
                            <th><?= $t('logs.th.action') ?></th>
                            <th><?= $t('logs.th.details') ?></th>
                            <th><?= $t('logs.th.ip') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($result['entries'] as $e): ?>
                            <tr class="<?= $e['action'] === 'auth.login_failed' ? 'log-row--warn' : '' ?>">
                                <td class="admin-table__muted log-nowrap"><?= $h(substr($e['time'], 11)) ?></td>
                                <td class="log-nowrap">
                                    <?php if ($e['user'] !== null || $e['user_id'] !== null): ?>
                                        <strong><?= $h($e['user'] ?? Lang::get('logs.unknown_user')) ?></strong>
                                        <?php if ($e['user_id'] !== null): ?>
                                            <span class="admin-table__muted">#<?= (int) $e['user_id'] ?></span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="admin-table__muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="log-nowrap">
                                    <span class="log-badge log-badge--<?= $h($e['category']) ?>"><?= $h($e['label']) ?></span>
                                </td>
                                <td><?= $formatData($e['data']) ?></td>
                                <td class="admin-table__muted log-nowrap"><?= $h($e['ip'] ?? '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>

            <?php else: ?>

                <?php $levels = LogReader::levels(); ?>
                <div class="log-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th><?= $t('logs.th.time') ?></th>
                            <th><?= $t('logs.th.level') ?></th>
                            <th><?= $t('logs.th.message') ?></th>
                            <th><?= $t('logs.th.user') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($result['entries'] as $e): ?>
                            <?php $ctx = $e['context']; ?>
                            <tr>
                                <td class="admin-table__muted log-nowrap"><?= $h(substr($e['time'], 11)) ?></td>
                                <td><span class="log-badge log-badge--<?= $h($e['level']) ?>"><?= $h($levels[$e['level']] ?? $e['level']) ?></span></td>
                                <td class="log-message">
                                    <?= $h($e['message']) ?>
                                    <?php if (!empty($ctx['method']) || !empty($ctx['uri'])): ?>
                                        <div class="admin-table__muted log-url"><?= $h(($ctx['method'] ?? '') . ' ' . ($ctx['uri'] ?? '')) ?></div>
                                    <?php endif; ?>
                                    <?php if ($e['details'] !== '' || $ctx): ?>
                                        <details class="log-details">
                                            <summary><?= $t('logs.details') ?></summary>
                                            <?php if ($e['details'] !== ''): ?>
                                                <pre><?= $h($e['details']) ?></pre>
                                            <?php endif; ?>
                                            <?php if ($ctx): ?>
                                                <pre><?= $h(json_encode($ctx, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></pre>
                                            <?php endif; ?>
                                        </details>
                                    <?php endif; ?>
                                </td>
                                <td class="admin-table__muted log-nowrap"><?= isset($ctx['user']) ? '#' . (int) $ctx['user'] : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>

            <?php endif; ?>

            <!-- Pagination -->
            <?php if ($result['pages'] > 1): ?>
                <nav class="log-pager">
                    <?php if ($result['page'] > 1): ?>
                        <a class="btn btn--sm btn--ghost" href="<?= $h($link(['page' => $result['page'] - 1])) ?>"><?= $t('logs.pager.newer') ?></a>
                    <?php endif; ?>
                    <span><?= $t('logs.pager.page', ['page' => (int) $result['page'], 'pages' => (int) $result['pages']]) ?></span>
                    <?php if ($result['page'] < $result['pages']): ?>
                        <a class="btn btn--sm btn--ghost" href="<?= $h($link(['page' => $result['page'] + 1])) ?>"><?= $t('logs.pager.older') ?></a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>

        <?php endif; ?>
    <?php endif; ?>

</section>
