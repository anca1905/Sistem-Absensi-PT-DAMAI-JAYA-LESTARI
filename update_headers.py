import os

files = [
    'karyawan/laporan_keseluruhan.php',
    'kerani/laporan_individu.php',
    'kerani/laporan_keseluruhan.php',
    'keuangan/lap_keseluruhan.php',
    'mandor/laporan_keseluruhan.php',
    'pengawas/laporan_keseluruhan.php',
    'pengawas/laporan_mingguan.php'
]

for f in files:
    with open(f, 'r', encoding='utf-8') as file:
        content = file.read()
    
    # 1. Remove O1 - and O2 -
    content = content.replace('O1 — ', '')
    content = content.replace('O2 — ', '')
    
    # 2. Modify T3 (Panen) to remove 'HASIL TBS (kg)' column
    # The header group for T3 needs colspan reduced by 1
    content = content.replace('<th colspan="7" class="th-o2">HASIL PANEN</th>', '<th colspan="6" class="th-o2">HASIL PANEN</th>')
    
    # The detail header
    content = content.replace('<th class="th-o2">HASIL TBS (kg)</th>', '')
    
    # The td for T3 (Panen) - we need to remove the td with hasil_ton.
    # In pengawas/laporan_mingguan.php and others:
    # <td class="td-num"><?= number_format($lb['hasil_ton'] ?? 0, 0) ?></td>
    # The next column is <td class="td-num"><?= number_format($lb['tandan_kosong'] ?? 0, 0) ?></td>
    # We will just replace the exact line.
    content = content.replace('<td class="td-num"><?= number_format($lb[\'hasil_ton\'] ?? 0, 0) ?></td>\n', '')
    
    # The footer td for sum_tbs_kg
    # <td style="text-align:right;"><?= number_format($sum_tbs_kg, 0) ?></td>
    content = content.replace('<td style="text-align:right;"><?= number_format($sum_tbs_kg, 0) ?></td>\n', '')
    
    with open(f, 'w', encoding='utf-8') as file:
        file.write(content)
    print(f'Updated {f}')
