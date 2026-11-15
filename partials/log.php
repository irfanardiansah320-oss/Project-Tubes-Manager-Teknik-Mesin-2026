<?php if (!$riwayat): ?><p class="empty">Belum ada catatan.</p><?php else: ?>
<div class="tablewrap"><table><tr><th>Waktu</th><th>Kode</th><th>Jumlah</th><th>Oleh</th><th>Catatan</th></tr>
<?php foreach ($riwayat as $l): ?><tr><td><?= e($l['waktu']) ?></td><td><?= e($l['kode']) ?></td><td><?= e($l['jumlah']) ?></td><td><?= e($l['oleh']) ?></td><td><?= e($l['catatan']) ?></td></tr><?php endforeach; ?>
</table></div><?php endif; ?>
