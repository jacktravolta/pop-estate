<?php
namespace App\Service\AI;
class SqlGuard
{
    private const ALLOWED_TABLES = ['company','owner','property','settlement','settlement_item','invoice','invoice_settlement'];
    private const BLOCKED_COLUMNS = ['password','embedding','roles'];
    private const BLOCKED_KEYWORDS = ['DROP','DELETE','UPDATE','INSERT','ALTER','CREATE','TRUNCATE','GRANT','REVOKE','EXEC','EXECUTE','MERGE','REPLACE','COPY','VACUUM','ANALYZE','CALL','DO','SHOW','SET','RESET'];
    public function validate(string $sql): bool
    {
        $trimmed = trim($sql);
        if ($trimmed === '') return false;
        $sqlUpper = ltrim(strtoupper($trimmed));
        $sqlUpper = preg_replace('/^\s*\/\*.*?\*\/\s*/s', '', $sqlUpper);
        if (!str_starts_with($sqlUpper, 'SELECT')) return false;
        if (strlen($sql) > 800) return false;
        $withoutStrings = preg_replace("/'[^']*'/", "''", $sql);
        if (substr_count($withoutStrings, ';') > 1) return false;
        if (str_contains($withoutStrings, ';') && !preg_match('/;\s*$/', $trimmed)) return false;
        foreach (self::BLOCKED_KEYWORDS as $kw) { if (preg_match('/\b'.$kw.'\b/i', $sql)) return false; }
        $blocked = ['/--/','/\/\*/','/\*\//','/\bpg_\w+/i','/\binformation_schema\b/i','/\bpg_catalog\b/i','/\bcurrent_user\b/i','/\bxp_\w+/i','/\binto\s+outfile\b/i'];
        foreach ($blocked as $pat) { if (preg_match($pat, $sql)) return false; }
        if (preg_match_all('/\b(?:FROM|JOIN)\s+["\']?(\w+)["\']?/i', $sql, $m)) {
            foreach ($m[1] as $t) {
                $t = strtolower(trim($t,'"\''));
                if (str_contains($t, '.')) $t = substr($t, strrpos($t,'.')+1);
                if (!in_array($t, self::ALLOWED_TABLES, true)) return false;
            }
        }
        foreach (self::BLOCKED_COLUMNS as $col) { if (preg_match('/\b'.$col.'\b/i', $sql)) return false; }
        return true;
    }
    public function enforceLimit(string $sql): string
    {
        $sql = trim(rtrim($sql, "; \n\r\t"));
        if (preg_match('/\bLIMIT\s+(\d+)/i', $sql, $m)) {
            if ((int)$m[1] > 100) $sql = preg_replace('/\bLIMIT\s+\d+/i','LIMIT 100',$sql);
            return $sql;
        }
        return $sql.' LIMIT 100';
    }
}
