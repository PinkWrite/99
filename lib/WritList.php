<?php
declare(strict_types=1);

/** Writer and editor tables: sort, search, pagination, status-colored first-column buttons. */
final class WritList
{
    private App $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function renderWriter(string $whereAmI, string $term = 'current'): void
    {
        $this->writTable($whereAmI, 'writer', $term, $this->app->auth->id(), null);
    }

    public function renderEditor(string $whereAmI, string $review = 'current'): void
    {
        $eid = $this->app->auth->is('editor') ? $this->app->auth->id() : null;
        $this->writTable($whereAmI, 'editor', $review, null, $eid);
    }

    /** @param list<int> $writerIds */
    public function renderObserver(string $whereAmI, array $writerIds): void
    {
        $this->writTable($whereAmI, 'observer', 'current', null, null, $writerIds);
    }

    public function dashSortBar(string $whereAmI, string $formId = 'searchformdash'): array
    {
        $st = $this->listState($whereAmI, ['activity', 'creation', 'work', 'title', 'status'], 'activity');
        $this->toolbar($st['where'], $st['sortGet'], $st['searchSuffix'], $st['classes'], $st['q'], $formId, [
            'activity' => ['Activity', 'Sort by most recent activity'],
            'creation' => ['Creation', 'Sort by order of creation'],
            'work' => ['Work', 'Sort by work'],
            'title' => ['Title', 'Sort by title'],
            'status' => ['Status', 'Sort by status'],
        ]);
        return $st;
    }

    public function renderNotes(string $whereAmI): void
    {
        $uid = $this->app->auth->id();
        $st = $this->listState($whereAmI, ['creation', 'heading'], 'heading');
        $where = ['n.writer_id = ?', "n.type = 'note'", "n.status = 'live'"];
        $params = [$uid];
        $this->appendSearch($where, $params, $st['q'], ['n.body']);
        $order = $st['sort'] === 'heading' ? 'n.body ASC' : 'n.id DESC';
        $this->printList(
            $st,
            "SELECT COUNT(*) FROM notes n WHERE " . implode(' AND ', $where),
            $params,
            "SELECT n.* FROM notes n WHERE " . implode(' AND ', $where) . " ORDER BY {$order}",
            function (array $rows) {
                if (!$rows) {
                    echo '<p class="lt sans">No notes</p>';
                    return;
                }
                $cc = 'lr';
                echo '<table class="list lt notes sans"><tbody>';
                foreach ($rows as $n) {
                    $id = (int) $n['id'];
                    $title = note_heading($n['body'] ?? '');
                    echo '<tr class="' . $cc . '">';
                    echo '<td><a class="listed_note" href="note.php?n=' . $id . '">' . h($title) . '</a><br /><i class="listed_note">' . h((string) $n['save_date']) . '</i></td>';
                    echo '<td><div style="display:inline;float:right">' . get_switch('Read', 'Read this note', 'note.php', 'n', (string) $id, 'act_blue editNoteButton') . '</div></td>';
                    echo '<td><div style="display:inline;float:right">' . get_switch('Edit', 'Edit this note', 'note.php', 'n', (string) $id, 'editNoteButton') . '</div></td>';
                    echo '<td><div style="display:inline;float:right">';
                    if (empty($n['pinned'])) {
                        echo post_button('Pin', 'Pin at the top of your Dashboard', 'note-act.php', 'pin', (string) $id, 'editNoteButton', $this->app->csrf->token());
                    } else {
                        echo post_button('Pinned', 'Unpin from Dashboard', 'note-act.php', 'unpin', (string) $id, 'act_green editNoteButton', $this->app->csrf->token());
                    }
                    echo '</div></td></tr>';
                    $cc = $cc === 'lr' ? 'dr' : 'lr';
                }
                echo '</tbody></table>';
            },
            [
                'creation' => ['Creation', 'Sort by order of creation'],
                'heading' => ['Heading', 'Sort by heading'],
            ],
            'searchformnotes'
        );
    }

    public function renderWriterBlocks(string $whereAmI): void
    {
        $u = $this->app->auth->user();
        $st = $this->listState($whereAmI, ['creation', 'name', 'code'], 'creation');
        $ids = $this->app->user->blocksOf($u);
        $where = ["b.status = 'open'"];
        $params = [];
        if ($ids) {
            $in = implode(',', array_map('intval', $ids));
            $where[] = "b.id IN ({$in})";
        } else {
            $where[] = '0=1';
        }
        $this->appendSearch($where, $params, $st['q'], ['b.name', 'b.code']);
        $order = match ($st['sort']) {
            'name' => 'b.name ASC',
            'code' => 'b.code ASC',
            default => 'b.id DESC',
        };
        $editorName = '';
        if (!empty($u['editor_id'])) {
            $ed = $this->app->user->find((int) $u['editor_id']);
            $editorName = $ed['name'] ?? '';
        }
        $this->printList(
            $st,
            "SELECT COUNT(*) FROM blocks b WHERE " . implode(' AND ', $where),
            $params,
            "SELECT b.*, u.name AS editor_name FROM blocks b LEFT JOIN users u ON u.id = b.editor_id WHERE " . implode(' AND ', $where) . " ORDER BY {$order}",
            function (array $rows) use ($st, $editorName) {
                echo '<table class="list sans lt"><tbody>';
                if ($st['q'] === '') {
                    echo '<tr><td><a class="listed_note" href="writs.php?v=0"><b>Main</b></a></td><td></td>';
                    echo '<td><span class="sans bt">' . h($editorName) . '</span></td>';
                    echo '<td><div style="display:inline;float:right">' . get_switch('Writs →', 'List my writs for this block', 'writs.php', 'v', '0', 'editNoteButton') . '</div></td>';
                    echo '<td><div style="display:inline;float:right">' . button('Memos →', 'List memos for Main block', 'memos.php', 'editNoteButton') . '</div></td>';
                    echo '<td><div style="display:inline;float:right">' . post_button('New writ +', 'Start a general task for this block', 'writ.php', 'new_writ', (string) $this->app->auth->id(), 'editNoteButton', $this->app->csrf->token()) . '</div></td>';
                    echo '</tr>';
                }
                $cc = 'lr';
                foreach ($rows as $b) {
                    $id = (int) $b['id'];
                    echo '<tr class="' . $cc . '">';
                    echo '<td><a class="listed_note" href="writs.php?v=' . $id . '"><b>' . h((string) $b['name']) . '</b></a></td>';
                    echo '<td><a class="listed_note" href="writs.php?v=' . $id . '">' . h((string) $b['code']) . '</a></td>';
                    echo '<td><span class="sans bt">' . h((string) ($b['editor_name'] ?? '')) . '</span></td>';
                    echo '<td><div style="display:inline;float:right">' . get_switch('Writs →', 'List my writs for this block', 'writs.php', 'v', (string) $id, 'editNoteButton') . '</div></td>';
                    echo '<td><div style="display:inline;float:right">' . get_switch('Memos →', 'List memos for this block', 'memos.php', 'b', (string) $id, 'editNoteButton') . '</div></td>';
                    echo '<td><div style="display:inline;float:right">' . post_button('New writ +', 'Start a general writ for this block', 'writ.php', 'new_writ', (string) $this->app->auth->id(), 'editNoteButton', $this->app->csrf->token()) . '</div></td>';
                    echo '</tr>';
                    $cc = $cc === 'lr' ? 'dr' : 'lr';
                }
                if (!$rows && $st['q'] !== '') {
                    echo '<tr><td colspan="4"><span class="lt sans">No other enrolled blocks</span></td></tr>';
                }
                echo '</tbody></table>';
            },
            [
                'creation' => ['Creation', 'Sort by order of creation'],
                'name' => ['Name', 'Sort by name'],
                'code' => ['Code', 'Sort by code'],
            ],
            'searchformblocks',
            true
        );
    }

    public function renderEditorBlocks(string $whereAmI): void
    {
        $uid = $this->app->auth->id();
        $st = $this->listState($whereAmI, ['creation', 'name', 'code'], 'creation');
        $where = ["b.status = 'open'"];
        $params = [];
        if ($this->app->auth->is('editor')) {
            $where[] = 'b.editor_id = ?';
            $params[] = $uid;
        } elseif ($this->app->auth->facilityId()) {
            $where[] = 'b.facility_id = ?';
            $params[] = $this->app->auth->facilityId();
        }
        $this->appendSearch($where, $params, $st['q'], ['b.name', 'b.code']);
        $order = match ($st['sort']) {
            'name' => 'b.name ASC',
            'code' => 'b.code ASC',
            default => 'b.id DESC',
        };
        $me = $this->app->auth->user();
        $this->printList(
            $st,
            "SELECT COUNT(*) FROM blocks b WHERE " . implode(' AND ', $where),
            $params,
            "SELECT b.*, u.name AS editor_name FROM blocks b LEFT JOIN users u ON u.id = b.editor_id WHERE " . implode(' AND ', $where) . " ORDER BY {$order}",
            function (array $rows) use ($st, $me) {
                echo '<table class="list sans lt"><tbody>';
                echo '<tr><th>Name</th><th>Code</th><th>Editor</th><th><div style="display:inline;float:right">Writs</div></th><th><div style="display:inline;float:right">Writers</div></th><th><div style="display:inline;float:right">Memos</div></th></tr>';
                if ($st['q'] === '') {
                    echo '<tr><td><a class="listed_note" href="writs-editor.php?v=0"><b>Main</b></a></td><td></td>';
                    echo '<td>' . h((string) ($me['name'] ?? '')) . '</td>';
                    echo '<td><div style="display:inline;float:right">' . get_switch('Writs', 'List writs in your main Block', 'writs-editor.php', 'v', '0', 'editNoteButton') . '</div></td>';
                    echo '<td><div style="display:inline;float:right">' . button('Writers', 'List writers in Main block', 'enrollment.php', 'editNoteButton') . '</div></td>';
                    echo '<td><div style="display:inline;float:right">' . button('Block notes', 'List memos for this block', 'memos-editor.php', 'editNoteButton') . '</div></td>';
                    echo '</tr>';
                }
                $cc = 'lr';
                foreach ($rows as $b) {
                    $id = (int) $b['id'];
                    echo '<tr class="' . $cc . '">';
                    echo '<td><a class="listed_note" href="block.php?b=' . $id . '"><b>' . h((string) $b['name']) . '</b></a></td>';
                    echo '<td><a class="listed_note" href="block.php?b=' . $id . '">' . h((string) $b['code']) . '</a></td>';
                    echo '<td>' . h((string) ($b['editor_name'] ?? '')) . '</td>';
                    echo '<td><div style="display:inline;float:right">' . get_switch('Writs', 'List writs in this Block', 'writs-editor.php', 'v', (string) $id, 'editNoteButton') . '</div></td>';
                    echo '<td><div style="display:inline;float:right">' . button('Writers', 'List writers', 'enrollment.php', 'editNoteButton') . '</div></td>';
                    echo '<td><div style="display:inline;float:right">' . get_switch('Block notes', 'List memos for this block', 'memos-editor.php', 'b', (string) $id, 'editNoteButton') . '</div></td>';
                    echo '</tr>';
                    $cc = $cc === 'lr' ? 'dr' : 'lr';
                }
                echo '</tbody></table>';
            },
            [
                'creation' => ['Creation', 'Sort by order of creation'],
                'name' => ['Name', 'Sort by name'],
                'code' => ['Code', 'Sort by code'],
            ],
            'searchformeditorblocks',
            true
        );
    }

    /** @param list<int> $writerIds */
    private function writTable(string $whereAmI, string $mode, string $status, ?int $writerId, ?int $editorId, array $writerIds = []): void
    {
        $st = $this->listState($whereAmI, ['activity', 'creation', 'work', 'title', 'status'], 'activity');
        $filterWriter = (int) ($_GET['u'] ?? $_GET['o'] ?? 0);
        $filterBlock = (int) ($_GET['v'] ?? 0);
        $blockCol = $this->writBlockCol();
        $where = [];
        $params = [];
        if ($mode === 'writer') {
            $where[] = 'w.writer_id = ?';
            $params[] = $writerId;
            $where[] = 'w.term_status = ?';
            $params[] = $status;
            if ($filterBlock > 0) {
                $where[] = "w.{$blockCol} = ?";
                $params[] = $filterBlock;
            }
        } elseif ($mode === 'observer') {
            if (!$writerIds) {
                echo '<p class="lt sans">No writs</p>';
                return;
            }
            $in = implode(',', array_fill(0, count($writerIds), '?'));
            $where[] = "w.writer_id IN ({$in})";
            foreach ($writerIds as $id) {
                $params[] = (int) $id;
            }
            $where[] = 'w.term_status = ?';
            $params[] = $status;
            if ($filterWriter > 0) {
                $where[] = 'w.writer_id = ?';
                $params[] = $filterWriter;
            }
            if ($filterBlock > 0) {
                $where[] = "w.{$blockCol} = ?";
                $params[] = $filterBlock;
            }
        } else {
            $where[] = 'w.review_status = ?';
            $params[] = $status;
            $editorCol = $this->userEditorCol();
            if ($editorId) {
                $where[] = "u.{$editorCol} = ?";
                $params[] = $editorId;
            }
            if ($filterWriter > 0) {
                $where[] = 'w.writer_id = ?';
                $params[] = $filterWriter;
            }
            if ($filterBlock > 0) {
                $where[] = "w.{$blockCol} = ?";
                $params[] = $filterBlock;
            }
        }
        $this->appendSearch($where, $params, $st['q'], ['w.work', 'w.title', 'w.draft', 'w.edits', 'w.correction', 'w.notes']);
        $whereSql = $where ? implode(' AND ', $where) : '1';
        $order = $this->orderSql($st['sort']);
        $this->printList(
            $st,
            "SELECT COUNT(*) FROM writs w LEFT JOIN users u ON u.id = w.writer_id WHERE {$whereSql}",
            $params,
            "SELECT w.*, u.name AS writer_name, b.name AS block_name, b.code AS block_code
             FROM writs w
             LEFT JOIN users u ON u.id = w.writer_id
             LEFT JOIN blocks b ON b.id = w.{$blockCol}
             WHERE {$whereSql}
             ORDER BY {$order}",
            function (array $rows) use ($mode, $status, $blockCol, $whereAmI) {
                if ($mode !== 'observer') {
                    $this->bulkBar($mode, $status, $this->app->auth->id(), $whereAmI);
                }
                if (!$rows) {
                    echo '<p class="lt sans">No writs</p>';
                    return;
                }
                echo '<table class="list writ lt sans"><tbody><tr><th></th><th>Work</th><th>Title</th>';
                if ($mode === 'writer') {
                    echo '<th>Block</th><th>Status</th><th>Edits</th><th>Score</th>';
                } else {
                    echo '<th>Status</th><th>Edits</th><th>Score</th><th>Writer</th><th>Block</th>';
                }
                echo '<th class="bulk_check"></th></tr>';
                $cc = 'lr';
                foreach ($rows as $w) {
                    $score = $w['score'];
                    $outof = $w['outof'];
                    $scoreHtml = ($score !== null && $outof !== null && (int) $score > (int) $outof)
                        ? '<span class="noticegreen">' . h((string) $score) . '</span>'
                        : h((string) $score);
                    $block = ((int) ($w[$blockCol] ?? $w['block_id'] ?? $w['block'] ?? 0) !== 0)
                        ? '<small title="' . h((string) ($w['block_name'] ?? '')) . '">' . h((string) ($w['block_code'] ?? '')) . '</small>'
                        : 'Main';
                    echo '<tr class="' . $cc . '"><td>';
                    echo $mode === 'writer' ? $this->writerAction($w) : ($mode === 'observer' ? $this->observerAction($w) : $this->editorAction($w));
                    echo '</td><td>' . h(writ_work($w['work'] ?? '', (int) $w['id'])) . '</td><td>' . h(writ_title($w['title'] ?? '')) . '</td>';
                    if ($mode === 'writer') {
                        echo '<td>' . $block . '</td><td>' . h((string) $w['draft_status']) . '</td><td>' . h((string) $w['edits_status']) . '</td><td>' . $scoreHtml . '<small class="dk">/' . h((string) $outof) . '</small></td>';
                    } else {
                        echo '<td>' . h((string) $w['draft_status']) . '</td><td>' . h((string) $w['edits_status']) . '</td><td>' . $scoreHtml . '<small class="dk">/' . h((string) $outof) . '</small></td><td>' . h((string) ($w['writer_name'] ?? '')) . '</td><td>' . $block . '</td>';
                    }
                    echo '<td class="bulk_check"><input form="bulk_actions" type="checkbox" name="bulk_' . (int) $w['id'] . '" value="' . (int) $w['id'] . '"></td></tr>';
                    $cc = $cc === 'lr' ? 'dr' : 'lr';
                }
                echo '</tbody></table>';
                if ($mode !== 'observer') {
                    $this->bulkScript();
                }
            },
            [
                'activity' => ['Activity', 'Sort by most recent activity'],
                'creation' => ['Creation', 'Sort by order of creation'],
                'work' => ['Work', 'Sort by work'],
                'title' => ['Title', 'Sort by title'],
                'status' => ['Status', 'Sort by status'],
            ],
            $mode === 'editor' ? 'searchformeditorwrits' : ($mode === 'observer' ? 'searchformobserverwrits' : 'searchform')
        );
    }

    /** @param list<string> $allowed */
    private function listState(string $whereAmI, array $allowed, string $default): array
    {
        $sort = preg_replace('/[^a-z]/', '', (string) ($_GET['s'] ?? $default)) ?: $default;
        if (!in_array($sort, $allowed, true)) {
            $sort = $default;
        }
        $page = (int) ($_GET['p'] ?? 1);
        if ($page < 1) {
            $page = 1;
        }
        $q = trim((string) ($_GET['r'] ?? ''));
        $q = preg_replace("/[^A-Za-z0-9 \'\/&,:%\-.!$?;]/", ' ', $q) ?? $q;
        $q = trim($q);
        $sortGet = str_contains($whereAmI, '?') ? '&' : '?';
        $classes = [];
        foreach ($allowed as $k) {
            $classes[$k] = 'act_ltgray';
        }
        $classes[$sort] = 'act_dkgray';
        $sortSuffix = $sort === $default ? '' : 's=' . $sort;
        return [
            'where' => $whereAmI,
            'sort' => $sort,
            'page' => $page,
            'q' => $q,
            'sortGet' => $sortGet,
            'sortSuffix' => $sortSuffix,
            'searchSuffix' => $q !== '' ? '&r=' . rawurlencode($q) : '',
            'classes' => $classes,
            'per' => $q === '' ? 250 : 1000,
        ];
    }

    /** @param list<string> $where @param list<mixed> $params @param list<string> $cols */
    private function appendSearch(array &$where, array &$params, string $q, array $cols): void
    {
        if ($q === '') {
            return;
        }
        $bits = [];
        foreach (preg_split('/\s+/', $q) ?: [] as $word) {
            if ($word === '') {
                continue;
            }
            $like = '%' . $word . '%';
            $ors = [];
            foreach ($cols as $col) {
                $ors[] = "{$col} LIKE ?";
                $params[] = $like;
            }
            $bits[] = '(' . implode(' OR ', $ors) . ')';
        }
        if ($bits) {
            $where[] = '(' . implode(' OR ', $bits) . ')';
        }
    }

    /**
     * @param array<string,array{0:string,1:string}> $sorts
     * @param callable(array):void $body
     */
    private function printList(array $st, string $countSql, array $params, string $selectSql, callable $body, array $sorts, string $formId, bool $keepEmpty = false): void
    {
        try {
            $total = (int) $this->app->db->val($countSql, $params);
        } catch (Throwable $e) {
            echo '<p class="sans noticered">List query failed: ' . h($e->getMessage()) . '</p>';
            return;
        }
        if ($total === 0 && $st['q'] === '' && !$keepEmpty) {
            echo '<p class="lt sans"><b>Nothing yet</b></p>';
            return;
        }
        $pages = max(1, (int) ceil($total / $st['per']));
        $page = $st['page'];
        if ($page > $pages) {
            $page = $pages;
        }
        $off = ($page - 1) * $st['per'];
        try {
            $rows = $this->app->db->all($selectSql . " LIMIT {$st['per']} OFFSET {$off}", $params);
        } catch (Throwable $e) {
            echo '<p class="sans noticered">List query failed: ' . h($e->getMessage()) . '</p>';
            return;
        }
        $this->pager($st['where'], $st['sortGet'], $st['sortSuffix'], $st['searchSuffix'], $page, $pages);
        $this->toolbar($st['where'], $st['sortGet'], $st['searchSuffix'], $st['classes'], $st['q'], $formId, $sorts);
        $body($rows);
        $this->pager($st['where'], $st['sortGet'], $st['sortSuffix'], $st['searchSuffix'], $page, $pages);
    }

    private function writBlockCol(): string
    {
        return $this->app->db && $this->app->db->columnExists('writs', 'block_id') ? 'block_id' : 'block';
    }

    private function userEditorCol(): string
    {
        return $this->app->db && $this->app->db->columnExists('users', 'editor_id') ? 'editor_id' : 'editor';
    }

    private function orderSql(string $sort): string
    {
        $act = 'GREATEST(
            COALESCE(w.draft_open_date,\'1970-01-01\'),
            COALESCE(w.draft_save_date,\'1970-01-01\'),
            COALESCE(w.draft_submit_date,\'1970-01-01\'),
            COALESCE(w.edits_date,\'1970-01-01\'),
            COALESCE(w.edits_viewed_date,\'1970-01-01\'),
            COALESCE(w.corrected_save_date,\'1970-01-01\'),
            COALESCE(w.corrected_submit_date,\'1970-01-01\'),
            COALESCE(w.scoring_date,\'1970-01-01\')
        ) DESC';
        return match ($sort) {
            'creation' => 'w.id DESC',
            'work' => 'w.work ASC',
            'title' => 'w.title ASC',
            'status' => "w.draft_status='submitted' DESC, w.edits_status='submitted' DESC, w.draft_status='reviewed' DESC, w.edits_status='drafting' DESC, w.edits_status='scored' DESC, w.draft_status='saved' DESC, w.id DESC",
            default => $act,
        };
    }

    private function writerAction(array $w): string
    {
        $id = (string) $w['id'];
        $ds = (string) $w['draft_status'];
        $es = (string) $w['edits_status'];
        $open = ((string) ($w['kind'] ?? 'writ') === 'test') ? 'take-test.php' : 'writ.php';
        if ($ds === 'saved' || $ds === 'redraft') {
            return get_switch('Open', 'Open to make available changes and updates', $open, 'w', $id, 'set_writ_orange');
        }
        if ($ds === 'reviewed' && in_array($es, ['drafting', 'viewed', 'saved'], true)) {
            return get_switch('Correct', 'Open to make final corrections', 'writ.php', 'w', $id, 'set_writ_green');
        }
        if ($ds === 'submitted' || $es === 'submitted') {
            return dead_switch('Submitted', "Can't open after submitted for review", 'set_writ_disabled');
        }
        if ($ds === 'reviewed' || $es === 'scored') {
            return get_switch('View', 'See the history and results', 'writ.php', 'w', $id, 'set_writ_blue');
        }
        return '';
    }

    private function observerAction(array $w): string
    {
        $id = (string) $w['id'];
        return get_switch('Open', 'Read this writ', 'writ.php', 'w', $id, 'set_writ_blue');
    }

    private function editorAction(array $w): string
    {
        $id = (string) $w['id'];
        $ds = (string) $w['draft_status'];
        $es = (string) $w['edits_status'];
        if ($ds === 'submitted') {
            return get_switch('Review', 'Open for review', 'review.php', 'w', $id, 'set_writ_orange');
        }
        if ($ds === 'reviewed' && $es === 'submitted') {
            return get_switch('Finish', 'Open for review', 'review.php', 'w', $id, 'set_writ_green');
        }
        if ($ds === 'saved' || $es === 'saved') {
            return get_switch('Peek', 'Preview current progress', 'review.php', 'w', $id, 'set_writ_gray');
        }
        if ($ds === 'reviewed' && $es === 'drafting') {
            return get_switch('Edited', 'Recheck draft review', 'review.php', 'w', $id, 'set_writ_blue');
        }
        if ($ds === 'reviewed' && $es === 'viewed') {
            return get_switch('View', 'Review current progress', 'review.php', 'w', $id, 'set_writ_gray');
        }
        if ($ds === 'reviewed' || $es === 'scored') {
            return get_switch('Scored', 'Recheck scoring', 'review.php', 'w', $id, 'set_writ_blue');
        }
        return '';
    }

    /** @param array<string,array{0:string,1:string}> $sorts */
    private function toolbar(string $where, string $sortGet, string $searchSuffix, array $cl, string $q, string $formId, array $sorts): void
    {
        $clean = str_contains($where, '?') ? (string) strstr($where, '?', true) : $where;
        $boxId = $formId . '_box';
        echo '<br><form id="' . h($formId) . '" action="' . h($clean) . '" method="get">';
        foreach ($_GET as $name => $value) {
            if ($name === 'r' || $name === 'p' || !is_scalar($value)) {
                continue;
            }
            echo '<input type="hidden" name="' . h((string) $name) . '" value="' . h((string) $value) . '">';
        }
        echo '</form>';
        echo '<div class="list-toolbar"><table class="plain"><tbody><tr><td><span class="lo sans">&uarr;&darr;</span></td>';
        foreach ($sorts as $key => $pair) {
            echo '<td>' . button($pair[0], $pair[1], $where . $sortGet . 's=' . $key . $searchSuffix, $cl[$key] ?? 'act_ltgray') . '</td>';
        }
        echo '<td><div class="search-input"><input type="text" name="r" placeholder="Search" form="' . h($formId) . '" id="' . h($boxId) . '" value="' . h($q) . '">';
        echo '<span data-clear-input onclick="searchClearReset(\'' . h($boxId) . '\',\'' . h($formId) . '\');" id="' . h($boxId) . '_clear">&times;</span></div></td><td>';
        echo '<label style="cursor:pointer"><svg width="28" height="28" xmlns="http://www.w3.org/2000/svg"><ellipse stroke="#bbb" stroke-width="3" ry="10" rx="10" cy="12" cx="12" fill="none"/><line stroke="#bbb" stroke-width="3" y2="27" x2="27" y1="18" x1="18" fill="none"/></svg>';
        echo '<input type="submit" form="' . h($formId) . '" value="Search" hidden></label></td></tr></tbody></table></div>';
        echo '<script>
if (typeof searchClearReset !== "function") {
  function searchClearReset(clearid, formid){document.getElementById(clearid).value="";document.getElementById(formid).submit();}
}
(function(){var b=document.getElementById(' . json_encode($boxId) . ');var c=document.getElementById(' . json_encode($boxId . '_clear') . ');if(!b||!c)return;c.style.display=b.value===""?"none":"block";b.addEventListener("keyup",function(){c.style.display=b.value===""?"none":"block";});})();
</script>';
    }

    private function pager(string $where, string $sortGet, string $sortSuffix, string $searchSuffix, int $page, int $pages): void
    {
        if ($pages <= 1) {
            return;
        }
        $base = $where . $sortGet . $sortSuffix . $searchSuffix . '&p=';
        $dis1 = $page === 1 ? ' disabled' : '';
        $disN = $page === $pages ? ' disabled' : '';
        $prev = max(1, $page - 1);
        $next = min($pages, $page + 1);
        echo '<div class="paginate_nav_container"><div class="paginate_nav"><table><tr>';
        echo '<td><a class="paginate' . $dis1 . '" title="Page 1" href="' . h($base . '1') . '">&laquo;</a></td>';
        echo '<td><a class="paginate' . $dis1 . '" title="Previous" href="' . h($base . $prev) . '">&lsaquo;&nbsp;</a></td>';
        echo '<td><a class="paginate current" href="' . h($base . $page) . '">Page ' . $page . ' (' . $pages . ')</a></td>';
        echo '<td><a class="paginate' . $disN . '" title="Next" href="' . h($base . $next) . '">&nbsp;&rsaquo;</a></td>';
        echo '<td><a class="paginate' . $disN . '" title="Last Page" href="' . h($base . $pages) . '">&raquo;</a></td>';
        echo '</tr></table></div></div>';
    }

    private function bulkBar(string $mode, string $status, int $uid, string $whereAmI = ''): void
    {
        $key = $mode === 'writer' ? 'writer_archive' : 'editor_archive';
        $return = preg_replace('/[?#].*$/', '', basename($whereAmI)) ?: ($mode === 'writer' ? 'writs.php' : 'editor.php');
        echo '<div class="bulk-bar"><form id="bulk_actions" class="bulk-bar-form" method="post" action="archive-act.php">';
        echo $this->app->csrf->field();
        echo '<input type="hidden" name="' . h($key) . '" value="' . $uid . '">';
        echo '<input type="hidden" name="return" value="' . h($return) . '">';
        echo '<span id="bulk_actions_div" class="bulk-opts" hidden>';
        if ($status === 'archived') {
            echo confirm_submit('bluksubmit', 'delete', 'Confirm delete', 'delete', 'act_red small', 'act_red small');
            echo confirm_submit('bluksubmit', 'restore', 'Confirm restore', 'restore', 'act_green small', 'act_green small');
        } else {
            echo confirm_submit('bluksubmit', 'archive all scored', 'Confirm archive all scored', 'archive all scored', 'act_blue small', 'act_blue small');
            echo confirm_submit('bluksubmit', 'archive', 'Confirm archive', 'archive', 'act_dkgray small', 'act_dkgray small');
        }
        echo '<label class="bulk-select-all"><small class="sans lt">Select all</small> <input type="checkbox" onclick="toggle(this)"></label>';
        echo '</span>';
        echo '<button type="button" class="act_ltgray small" id="bulk_actions_btn" onclick="showBulkActions()">Archive actions &#9660;</button>';
        echo '</form></div>';
    }

    private function bulkScript(): void
    {
        echo '<script>
function showBulkActions(){
  var bar = document.getElementById("bulk_actions_div");
  if (!bar) return;
  var open = bar.hidden;
  bar.hidden = !open;
  var tables = document.querySelectorAll("table.list.writ");
  for (var i = 0; i < tables.length; i++) {
    tables[i].classList.toggle("bulk-open", open);
  }
}
function toggle(source){
  var cb = document.querySelectorAll("table.list.writ td.bulk_check input[type=checkbox]");
  for (var i = 0; i < cb.length; i++) cb[i].checked = source.checked;
}
</script>';
    }
}
