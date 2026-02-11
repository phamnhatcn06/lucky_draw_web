<?php
/* @var $this AdminController */
/* @var $prizes Prize[] */
/* @var $participantsCount int */
/* @var $activeCount int */
/* @var $lastWinners array */
/* @var $settings array */
/* @var $allowMulti bool */

$accessCode = isset($settings['access_code']) ? $settings['access_code'] : 'YEP6868';
$eventTitle = isset($settings['event_title']) ? $settings['event_title'] : 'Lucky Draw';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Admin - <?php echo CHtml::encode($eventTitle); ?></title>
    <style>
        :root {
            --bg-color: #0f172a;
            --sidebar-bg: #1e293b;
            --text-color: #e2e8f0;
            --accent-color: #3b82f6;
            --accent-hover: #2563eb;
            --border-color: #334155;
            --card-bg: #1e293b;
            --danger: #ef4444;
            --success: #22c55e;
            --warning: #f59e0b;
        }

        body {
            margin: 0;
            font-family: system-ui, -apple-system, sans-serif;
            background: var(--bg-color);
            color: var(--text-color);
            display: flex;
            min-height: 100vh;
        }

        /* Layout */
        .admin-sidebar {
            width: 250px;
            background: var(--sidebar-bg);
            padding: 20px;
            display: flex;
            flex-direction: column;
            border-right: 1px solid var(--border-color);
            flex-shrink: 0;
        }

        .admin-content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }

        /* Sidebar */
        .brand {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 30px;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-item {
            padding: 12px 15px;
            margin-bottom: 5px;
            cursor: pointer;
            border-radius: 8px;
            transition: .2s;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #94a3b8;
        }

        .nav-item:hover {
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
        }

        .nav-item.active {
            background: var(--accent-color);
            color: #fff;
        }

        .nav-footer {
            margin-top: auto;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }

        .logout-btn {
            color: #f87171;
            text-decoration: none;
            display: block;
            padding: 10px;
        }

        /* Tabs */
        .tab-pane {
            display: none;
            animation: fadeIn .3s ease;
        }

        .tab-pane.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(5px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Components */
        .card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        h2 {
            margin-top: 0;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 15px;
            margin-bottom: 20px;
            font-size: 24px;
        }

        h3 {
            margin-top: 0;
            font-size: 18px;
            margin-bottom: 15px;
            color: #fff;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        .stat-card {
            background: #334155;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }

        .stat-val {
            font-size: 32px;
            font-weight: bold;
            color: #fff;
            margin: 5px 0;
        }

        .stat-label {
            font-size: 14px;
            opacity: 0.7;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Forms */
        .form-group {
            margin-bottom: 15px;
        }

        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid var(--border-color);
            background: #0f172a;
            color: #fff;
            box-sizing: border-box;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent-color);
        }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: .2s;
        }

        .btn-primary {
            background: var(--accent-color);
            color: #fff;
        }

        .btn-primary:hover {
            background: var(--accent-hover);
        }

        .btn-danger {
            background: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.5);
        }

        .btn-danger:hover {
            background: rgba(239, 68, 68, 0.4);
        }

        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 12px;
            border-bottom: 2px solid var(--border-color);
            color: #94a3b8;
            font-size: 14px;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid var(--border-color);
        }

        tr:last-child td {
            border-bottom: none;
        }

        input.table-input {
            background: transparent;
            border: 1px solid transparent;
            color: #fff;
            padding: 4px;
            width: 100%;
            border-radius: 4px;
        }

        input.table-input:focus {
            border-color: var(--accent-color);
            background: #0f172a;
        }

        /* Responsive */
        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }

            .admin-sidebar {
                width: 100%;
                padding: 10px;
                flex-direction: row;
                align-items: center;
                overflow-x: auto;
            }

            .admin-content {
                padding: 15px;
            }

            .nav-footer {
                display: none;
            }

            .brand {
                margin-bottom: 0;
                margin-right: 20px;
            }
        }
    </style>
</head>

<body>

    <div class="admin-sidebar">
        <div class="brand">🎲 ADMIN</div>
        <div class="nav-item active" onclick="switchTab('dashboard', this)">📊 Dashboard</div>
        <div class="nav-item" onclick="switchTab('prizes', this)">🎁 Giải thưởng</div>
        <div class="nav-item" onclick="switchTab('settings', this)">⚙️ Cấu hình</div>
        <div class="nav-item" onclick="switchTab('data', this)">💾 Dữ liệu</div>

        <div class="nav-footer">
            <a href="/" target="_blank" class="nav-item">🖥 Màn hình quay</a>
            <a href="<?php echo $this->createUrl('admin/logout'); ?>" class="logout-btn">Đăng xuất</a>
        </div>
    </div>

    <div class="admin-content">
        <?php if ($msg = Yii::app()->user->getFlash('success')): ?>
            <div
                style="background: rgba(34, 197, 94, 0.2); color: #4ade80; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid rgba(34, 197, 94, 0.4);">
                ✅ <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <!-- DASHBOARD -->
        <div id="dashboard" class="tab-pane active">
            <h2>Tổng quan</h2>
            <div class="grid">
                <div class="stat-card">
                    <div class="stat-val"><?php echo number_format($participantsCount); ?></div>
                    <div class="stat-label">Tổng người tham gia</div>
                </div>
                <div class="stat-card">
                    <div class="stat-val" style="color:#4ade80"><?php echo number_format($activeCount); ?></div>
                    <div class="stat-label">Đang có mặt (Active)</div>
                </div>
                <div class="stat-card">
                    <div class="stat-val" style="color:#facc15"><?php echo count($prizes); ?></div>
                    <div class="stat-label">Loại giải thưởng</div>
                </div>
            </div>

            <div class="card" style="margin-top: 20px;">
                <h3>🏆 Người trúng giải gần nhất</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Thời gian</th>
                            <th>Giải</th>
                            <th>Mã NV</th>
                            <th>Họ tên</th>
                            <th>Phòng ban</th>
                            <th>Công ty</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lastWinners as $w): ?>
                            <tr>
                                <td style="opacity:0.7;font-size:13px">
                                    <?php echo date('H:i:s d/m', strtotime($w['won_at'])); ?>
                                </td>
                                <td style="color:#facc15"><?php echo CHtml::encode($w['prize_name']); ?></td>
                                <td><b><?php echo CHtml::encode($w['code']); ?></b></td>
                                <td><?php echo CHtml::encode($w['full_name']); ?></td>
                                <td><?php echo CHtml::encode($w['department']); ?></td>
                                <td><?php echo CHtml::encode($w['company']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($lastWinners)): ?>
                            <tr>
                                <td colspan="6" style="text-align:center;padding:20px;opacity:0.5">Chưa có ai trúng giải
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- PRIZES -->
        <div id="prizes" class="tab-pane">
            <h2>Quản lý Giải Thưởng</h2>
            <div class="card">
                <form method="post" action="<?php echo $this->createUrl('admin/savePrizes'); ?>">
                    <table>
                        <thead>
                            <tr>
                                <th width="80">Thứ tự</th>
                                <th>Tên giải thưởng</th>
                                <th width="100">Số lượng</th>
                                <th width="120">ID</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($prizes as $p): ?>
                                <tr>
                                    <td><input type="number" class="table-input" name="prize_order[<?php echo $p->id; ?>]"
                                            value="<?php echo $p->prize_order; ?>"></td>
                                    <td><input type="text" class="table-input" name="prize_name[<?php echo $p->id; ?>]"
                                            value="<?php echo CHtml::encode($p->prize_name); ?>"></td>
                                    <td><input type="number" class="table-input" name="quantity[<?php echo $p->id; ?>]"
                                            value="<?php echo $p->quantity; ?>"></td>
                                    <td style="opacity:0.5">#<?php echo $p->id; ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr style="border-top: 2px dashed var(--border-color);">
                                <td><input type="number" class="form-control" name="new_prize_order" placeholder="STT">
                                </td>
                                <td><input type="text" class="form-control" name="new_prize_name"
                                        placeholder="Thêm giải mới..."></td>
                                <td><input type="number" class="form-control" name="new_quantity" placeholder="SL"></td>
                                <td><span style="font-size:12px;color:#22c55e">+ New</span></td>
                            </tr>
                        </tbody>
                    </table>
                    <div style="margin-top: 20px; text-align: right;">
                        <button class="btn btn-primary">Lưu thay đổi</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- SETTINGS -->
        <div id="settings" class="tab-pane">
            <h2>Cấu hình hệ thống</h2>
            <div class="card" style="max-width: 600px;">
                <form method="post" action="<?php echo $this->createUrl('admin/settings'); ?>">
                    <div class="form-group">
                        <label class="form-label">Giải thưởng đang quay (Current Prize)</label>
                        <select class="form-control" name="settings[current_prize_id]">
                            <option value="">-- Chọn giải đang quay --</option>
                            <?php
                            $currentPrizeId = isset($settings['current_prize_id']) ? $settings['current_prize_id'] : '';
                            foreach ($prizes as $p):
                                ?>
                                <option value="<?php echo $p->id; ?>" <?php if ($p->id == $currentPrizeId)
                                       echo 'selected'; ?>>
                                    <?php echo CHtml::encode($p->prize_name); ?> (SL: <?php echo $p->quantity; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tên chương trình (Event Title)</label>
                        <input type="text" class="form-control" name="settings[event_title]"
                            value="<?php echo CHtml::encode($eventTitle); ?>" placeholder="Ví dụ: Tiệc Tất Niên 2026">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Mã Check-in / Mở khóa (Access Code)</label>
                        <input type="text" class="form-control" name="settings[access_code]"
                            value="<?php echo CHtml::encode($accessCode); ?>" placeholder="Mặc định: YEP6868">
                        <small style="opacity:0.6; display:block; margin-top:5px">Mã này dùng để người dùng đăng nhập
                            vào trang quay số.</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Cài đặt khác</label>
                        <div style="background: rgba(0,0,0,0.2); padding: 10px; border-radius: 6px;">
                            <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
                                <input type="checkbox" disabled <?php if ($allowMulti)
                                    echo 'checked'; ?>>
                                Cho phép trúng nhiều lần (Cài đặt trong file config)
                            </label>
                        </div>

                        <div
                            style="background: rgba(239, 68, 68, 0.1); padding: 15px; border-radius: 6px; border: 1px solid rgba(239, 68, 68, 0.3); margin-top: 15px;">
                            <label
                                style="display:flex;align-items:center;gap:10px;cursor:pointer;margin-bottom:10px;font-weight:bold;color:#fca5a5">
                                <input type="hidden" name="settings[exclude_active]" value="0">
                                <input type="checkbox" name="settings[exclude_active]" value="1" <?php if (!empty($settings['exclude_active']))
                                    echo 'checked'; ?>>
                                LOẠI BỎ THEO TỪ KHÓA (Ví dụ: "Nhà thầu")
                            </label>
                            <input type="text" class="form-control" name="settings[exclude_keyword]"
                                value="<?php echo CHtml::encode(isset($settings['exclude_keyword']) ? $settings['exclude_keyword'] : 'Nhà thầu'); ?>"
                                placeholder="Từ khóa cần lọc (VD: Nhà thầu)">
                            <small style="opacity:0.7;display:block;margin-top:5px">Những người có Tên/Phòng ban/Công ty
                                chứa từ khóa này sẽ bị loại khỏi vòng quay.</small>
                        </div>

                        <div
                            style="background: rgba(59, 130, 246, 0.1); padding: 15px; border-radius: 6px; border: 1px solid rgba(59, 130, 246, 0.3); margin-top: 15px;">
                            <label
                                style="display:flex;align-items:center;gap:10px;cursor:pointer;margin-bottom:5px;font-weight:bold;color:#93c5fd">
                                <input type="hidden" name="settings[exclude_partners]" value="0">
                                <input type="checkbox" name="settings[exclude_partners]" value="1" <?php if (!empty($settings['exclude_partners']))
                                    echo 'checked'; ?>>
                                LOẠI BỎ "ĐỐI TÁC" (is_partner=1)
                            </label>
                            <small style="opacity:0.7;display:block;">Bỏ qua những người có đánh dấu <b>is_partner=1</b>
                                khi upload.</small>
                        </div>
                    </div>

                    <div style="margin-top: 30px;">
                        <button class="btn btn-primary">Lưu cấu hình</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- DATA -->
        <div id="data" class="tab-pane">
            <h2>Dữ liệu & Reset</h2>

            <div class="grid">
                <div class="card">
                    <h3>📤 Upload Danh sách (CSV)</h3>
                    <p style="opacity:0.7; font-size:14px; margin-bottom:15px">
                        File CSV cần có header: <code>code, full_name, department, company</code>.<br>
                        Các mã nhân viên trùng sẽ được cập nhật thông tin mới.
                    </p>
                    <form method="post" enctype="multipart/form-data"
                        action="<?php echo $this->createUrl('admin/uploadParticipants'); ?>">
                        <input type="file" name="csv" accept=".csv,text/csv" required class="form-control"
                            style="margin-bottom:15px">
                        <button class="btn btn-primary">Upload CSV</button>
                    </form>
                </div>

                <div class="card" style="border-color: rgba(34, 197, 94, 0.3)">
                    <h3 style="color:#4ade80">📥 Xuất Danh sách Trúng giải</h3>
                    <p style="opacity:0.7; font-size:14px; margin-bottom:15px">
                        Tải xuống danh sách toàn bộ nhân viên đã trúng giải (file .csv).
                    </p>
                    <a href="<?php echo $this->createUrl('admin/exportWinners'); ?>" target="_blank"
                        class="btn btn-primary"
                        style="background:#22c55e;text-decoration:none;display:inline-block">Xuất Excel</a>
                </div>

                <div class="card" style="border-color: rgba(239, 68, 68, 0.3)">
                    <h3 style="color:#f87171">⚠️ Reset Dữ liệu Trúng thưởng</h3>
                    <p style="opacity:0.7; font-size:14px; margin-bottom:15px">
                        Hành động này sẽ <b>XÓA TOÀN BỘ</b> danh sách người trúng giải.<br>
                        Danh sách người tham gia (Participants) vẫn được giữ nguyên.
                    </p>
                    <form method="post" action="<?php echo $this->createUrl('admin/resetWinners'); ?>"
                        onsubmit="return confirm('CẢNH BÁO: Bạn có chắc chắn muốn xóa toàn bộ lịch sử trúng giải không? Hành động này không thể hoàn tác!');">
                        <button class="btn btn-danger">Xóa toàn bộ Winners</button>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <script>
        function switchTab(tabId, navEl) {
            // Hide all tabs
            document.querySelectorAll('.tab-pane').forEach(el => el.classList.remove('active'));
            // Show target tab
            document.getElementById(tabId).classList.add('active');

            // Update nav
            document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
            if (navEl) navEl.classList.add('active');

            // Save state
            localStorage.setItem('admin_tab', tabId);
        }

        // Restore tab
        const savedTab = localStorage.getItem('admin_tab');
        if (savedTab) {
            const nav = document.querySelector(`.nav-item[onclick="switchTab('${savedTab}', this)"]`);
            if (nav) switchTab(savedTab, nav);
        }
    </script>

</body>

</html>