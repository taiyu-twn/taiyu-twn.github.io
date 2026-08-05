<?php
require_once __DIR__ . '/../config/google.php';

// ── 基礎讀寫 ────────────────────────────────────────────────

function readSheet(string $sheetName): array {
    $service = getSheetsService();
    $response = $service->spreadsheets_values->get(GOOGLE_SHEET_ID, $sheetName);
    $rows = $response->getValues() ?? [];
    if (count($rows) < 2) return [];

    $headers = array_shift($rows);
    $result  = [];
    foreach ($rows as $i => $row) {
        $padded = array_pad($row, count($headers), '');
        $result[] = array_combine($headers, $padded);
    }
    return $result;
}

function appendSheet(string $sheetName, array $values): void {
    $service = getSheetsService();
    $body = new Google\Service\Sheets\ValueRange(['values' => [$values]]);
    $service->spreadsheets_values->append(
        GOOGLE_SHEET_ID,
        $sheetName,
        $body,
        ['valueInputOption' => 'USER_ENTERED']
    );
}

function updateSheet(string $sheetName, int $rowIndex, array $values): void {
    // rowIndex 從 1 開始（不含標頭列，標頭為第 1 列，資料從第 2 列起）
    $row     = $rowIndex + 1;
    $col     = columnLetter(count($values));
    $range   = "{$sheetName}!A{$row}:{$col}{$row}";
    $service = getSheetsService();
    $body    = new Google\Service\Sheets\ValueRange(['values' => [$values]]);
    $service->spreadsheets_values->update(
        GOOGLE_SHEET_ID,
        $range,
        $body,
        ['valueInputOption' => 'USER_ENTERED']
    );
}

function updateSheetCell(string $sheetName, int $rowIndex, string $column, mixed $value): void {
    $row   = $rowIndex + 1;
    $range = "{$sheetName}!{$column}{$row}";
    $service = getSheetsService();
    $body    = new Google\Service\Sheets\ValueRange(['values' => [[$value]]]);
    $service->spreadsheets_values->update(
        GOOGLE_SHEET_ID,
        $range,
        $body,
        ['valueInputOption' => 'USER_ENTERED']
    );
}

// ── 過濾查詢（減少 API 呼叫） ────────────────────────────────

function filterByColumn(string $sheetName, string $column, string $value): array {
    $rows = readSheet($sheetName);
    return array_values(array_filter($rows, fn($r) => ($r[$column] ?? '') === $value));
}

function filterByColumns(string $sheetName, array $conditions): array {
    $rows = readSheet($sheetName);
    return array_values(array_filter($rows, function ($r) use ($conditions) {
        foreach ($conditions as $col => $val) {
            if (($r[$col] ?? '') !== $val) return false;
        }
        return true;
    }));
}

function findRowIndex(string $sheetName, string $column, string $value): int {
    $rows = readSheet($sheetName);
    foreach ($rows as $i => $row) {
        if (($row[$column] ?? '') === $value) return $i + 1; // 1-based
    }
    return -1;
}

// ── 防重複查詢 ───────────────────────────────────────────────

function checkDuplicateVote(string $idHash, string $videoId, string $today): bool {
    $rows = filterByColumns(SHEET_VOTES, [
        'id_number_hash' => $idHash,
        'video_id'       => $videoId,
        'vote_date'      => $today,
    ]);
    return count($rows) > 0;
}

function checkDuplicateSign(string $idHash): bool {
    $rows = filterByColumn(SHEET_SIGNATURES, 'id_number_hash', $idHash);
    return count($rows) > 0;
}

function checkDuplicateUser(string $phone): bool {
    $rows = filterByColumn(SHEET_USERS, 'phone', $phone);
    return count($rows) > 0;
}

function getUserByPhone(string $phone): ?array {
    $rows = filterByColumn(SHEET_USERS, 'phone', $phone);
    return $rows[0] ?? null;
}

function getUserById(string $userId): ?array {
    $rows = filterByColumn(SHEET_USERS, 'user_id', $userId);
    return $rows[0] ?? null;
}

function getSubmissionById(string $id): ?array {
    $rows = filterByColumn(SHEET_SUBMISSIONS, 'id', $id);
    return $rows[0] ?? null;
}

function getApprovedVideos(): array {
    return filterByColumn(SHEET_SUBMISSIONS, 'status', 'approved');
}

function countVotesForVideo(string $videoId): int {
    $rows = filterByColumn(SHEET_VOTES, 'video_id', $videoId);
    return count($rows);
}

function countSignatures(): int {
    $rows = readSheet(SHEET_SIGNATURES);
    return count($rows);
}

function getTodayVoteCount(): int {
    $today = date('Y-m-d');
    $rows  = filterByColumn(SHEET_VOTES, 'vote_date', $today);
    return count($rows);
}

// ── 輔助函式 ─────────────────────────────────────────────────

function columnLetter(int $n): string {
    $letters = '';
    while ($n > 0) {
        $n--;
        $letters = chr(65 + ($n % 26)) . $letters;
        $n = intdiv($n, 26);
    }
    return $letters;
}

function hashId(string $idNumber): string {
    return hash('sha256', HASH_SALT . $idNumber);
}

function generateId(string $prefix = ''): string {
    return $prefix . date('YmdHis') . substr(bin2hex(random_bytes(4)), 0, 8);
}
