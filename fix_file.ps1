$f = 'c:\laragon\www\tom\wp-content\plugins\AB Split Test Lite\bt-bb-ab.php'
$l = Get-Content $f
$n = $l[0..11248] + $l[11842..($l.Length-1)]
[System.IO.File]::WriteAllLines($f, $n)
Write-Host 'done'
