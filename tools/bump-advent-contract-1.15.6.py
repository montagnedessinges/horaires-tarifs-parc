from pathlib import Path
p=Path('tests/advent-contract.php')
text=p.read_text()
old="advent_check(strpos($main, 'Version: 1.15.5') !== false && strpos($main, \"PARCS_HT_VERSION', '1.15.5\") !== false, 'Advent preview color fix uses version 1.15.5');"
new="advent_check(strpos($main, 'Version: 1.15.6') !== false && strpos($main, \"PARCS_HT_VERSION', '1.15.6\") !== false, 'Advent preview color fix remains active in version 1.15.6');"
if text.count(old)!=1:
    raise SystemExit('Advent contract version marker not found exactly once')
p.write_text(text.replace(old,new,1))
