<?php
if (!is_user_logged_in()) {
    auth_redirect();
}

$user = wp_get_current_user();
$storeCode = $user->data->user_login;
$store = $wpdb->get_results("SELECT * from store s");
global $wpdb;
if ($storeCode == 'qlcd' || $storeCode == 'daotao' || $storeCode == 'ksnb' || $storeCode == 'tocotocotea') {
    $storeDetail = $wpdb->get_results("SELECT b.* FROM {$wpdb->prefix}users a inner join store b on a.user_login = b.store_id", OBJECT)[0];
    $banthanhpham = $wpdb->get_results("SELECT * FROM banthanhpham", OBJECT);
    $sonaudoToDay = $wpdb->get_results("SELECT s.*,b.name_product from sonaudo s JOIN banthanhpham b on s.banthanhpham_id=b.id where DATE(s.created_date) = CURDATE()", OBJECT);
    $sonaudoYtd = $wpdb->get_results("SELECT s.*,b.name_product from sonaudo s JOIN banthanhpham b on s.banthanhpham_id=b.id where DATE(s.created_date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)", OBJECT);
    $sonaudoLastWeek = $wpdb->get_results("SELECT s.*,b.name_product from sonaudo s JOIN banthanhpham b on s.banthanhpham_id=b.id where DATE(s.created_date) = DATE_SUB(CURDATE(), INTERVAL 1 WEEK)", OBJECT);

} else {
    $storeDetail = $wpdb->get_results("SELECT b.* FROM {$wpdb->prefix}users a inner join store b on a.user_login = b.store_id  WHERE a.user_login = {$storeCode}", OBJECT)[0];
    $banthanhpham = $wpdb->get_results("SELECT * FROM banthanhpham", OBJECT);
    $sonaudoToDay = $wpdb->get_results("SELECT s.*,b.name_product from sonaudo s JOIN banthanhpham b on s.banthanhpham_id=b.id where DATE(s.created_date) = CURDATE() and s.store_id={$storeCode}", OBJECT);
    $sonaudoYtd = $wpdb->get_results("SELECT s.*,b.name_product from sonaudo s JOIN banthanhpham b on s.banthanhpham_id=b.id where DATE(s.created_date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) and s.store_id={$storeCode}", OBJECT);
    $sonaudoLastWeek = $wpdb->get_results("SELECT s.*,b.name_product from sonaudo s JOIN banthanhpham b on s.banthanhpham_id=b.id where DATE(s.created_date) = DATE_SUB(CURDATE(), INTERVAL 1 WEEK)  and s.store_id={$storeCode}", OBJECT);

}

?>
<html lang="en" style="margin-top: 0 !important;">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sổ nấu đồ
        <?php echo ($storeCode == 'qlcl' || $storeCode == 'daotao' || $storeCode == 'ksnb' || $storeCode == 'tocotocotea') ? "" : $storeDetail->store_name; ?>
    </title>
    <script>
        localStorage.setItem('storeCode', <?php echo ($storeCode == 'qlcl' || $storeCode == 'daotao' || $storeCode == 'ksnb' || $storeCode == 'tocotocotea') ? 0 : $storeCode ?>)
        localStorage.setItem('storeDetail', JSON.stringify(<?php echo json_encode($storeDetail) ?>))
    </script> <?php wp_head() ?>
    <!-- abc -->
    <link rel='stylesheet' href='<?php echo ASSETS_URL . '/css/common.css' ?>' type='text/css' />
    <script async src='<?php echo ASSETS_URL . '/js/common.js' ?>'></script>
    <!-- abc -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.3.3/jspdf.min.js"></script>
    <link href="https://cdn.datatables.net/1.13.1/css/jquery.dataTables.min.css" rel="stylesheet" />
    <link href="https://cdn.datatables.net/buttons/2.3.2/css/buttons.dataTables.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.0/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.2/js/buttons.html5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
    <script type='text/javascript' src='https://tocotocotea.com/wp-includes/js/dist/vendor/moment.min.js?ver=2.29.2'
        id='moment-js'></script>
    <style>
        @media only screen and (max-width: 600px) {

            th:not(:first-child),
            td:not(:first-child) {
                min-width: 200px !important;
            }

            th.sorting {
                min-width: 200px !important;
            }

            th.sorting:first-child {
                min-width: 50px !important;
            }

            div.dt-buttons,
            .dataTables_wrapper .dataTables_filter {
                float: left !important;
            }

            div#tbToDaySum_wrapper {
                overflow: scroll;
                width: 100% !important;
                max-width: 100%;
            }

            .modal.show .modal-dialog.modal-lg {
                max-width: 100% !important;
            }
        }

        #todayModalSum div#tbToDaySum_length,
        #todayModalSum div#tbToDaySum_filter,
        #todayModalSum div#tbToDaySum_info,
        #todayModalSum div#tbToDaySum_paginate {
            display: none !important;
        }

        #tbToDay td {
            width: 200px !important;
            min-width: 200px !important;
        }

        #tbToDay td:first-child,
        .dataTables_scroll th.sorting:first-child,
        #tbToDaySum_wrapper th:first-child,
        #tbToDaySum_wrapper td:first-child {
            width: 50px !important;
            min-width: 50px !important;
        }

        .dataTables_scroll th.sorting {
            width: 200px !important;
            min-width: 200px;
        }

        #tbToDaySum_wrapper th,
        #tbToDaySum_wrapper td {
            width: 200px !important;
            min-width: 200px;
        }

        div#tbToDaySum_wrapper {
            overflow: scroll;
            width: 100% !important;
            max-width: 100%;
        }

        #tbToDaySum_wrapper th:last-child,
        #tbToDaySum_wrapper td:last-child {
            min-width: 50px !important;
        }

        #tbData th,
        #tbData td {
            width: 200px;
            min-width: 200px !important;
        }

        #tbData th:first-child,
        #tbData td:first-child {
            width: 50px !important;
            min-width: 50px !important;
        }

        #tbData th:last-child,
        #tbData td:last-child {
            width: 300px !important;
            min-width: 300px !important;
        }

        .modal-dialog.modal-lg {
            max-width: 80% !important;
        }
    </style>
</head>

<body>
    <button type="button" class="btn btn-success btn-start-receipt-order">Bắt đầu sử dụng</button>
    <div class="order-admin page-customer-order">
        <div class="header-bar">
            <div class="left">Sổ nấu đồ</div>
            <div class="right">
                <b><?php echo "(" . $storeCode . ") " . $storeDetail->store_name . ': ' ?></b><a
                    href="<?php echo wp_logout_url() ?>">Đăng xuất</a>
            </div>
        </div>
        <div class="order-admin-content">
            <div class="container-fluid">
                <div class="container">
                    <div id="form_add_store mb-5">
                        <div class="form-row">
                            <div class="row col-12 col-md-12 mt-3 mb-3 ml-1">
                                <div class="col-6 col-md-6 col-sm-12 text-left pl-0">
                                    <div class="btn-group">
                                        <?php if ($storeCode != 0) { ?>
                                            <button id="submit_form" type="button" class="btn btn-default btn-success"
                                                style=" border-color: #ccc;">Lưu lại</button>
                                        <?php } ?>
                                        <button id="saveData" type="button" class="btn btn-default btn-success"
                                            style=" border-color: #ccc;">Lưu lại dữ liệu</button>
                                        <button type="button" class="btn btn-secondary" data-toggle="modal"
                                            data-target="#todayModal" style=" border-color: #ccc;">Lịch sử nấu
                                            đồ</button>
                                        <!--<button type="button" class="btn btn-info" data-toggle="modal" data-target="#todayModalSum" style=" border-color: #ccc;">Tổng hợp nấu-->
                                        <!--    đồ</button>-->
                                        <!--<a id="downloadFile" type="button" class="btn btn-light" style=" border-color: #ccc;" href="<?php echo WP_SITEURL . "/wp-content/themes/tocotocotea/assets/excel/sonaudo.xlsx" ?>">-->
                                        <!--    Tải xuống file mẫu</a>-->
                                        <?php if ($storeCode != 0) { ?>
                                            <button type="button" class="btn btn-info" data-toggle="modal"
                                                data-target="#todayModalSum" style=" border-color: #ccc;">Hủy đồ</button>
                                        <?php } ?>
                                    </div>
                                </div>
                                <!--<div class="col-6 col-md-6 col-sm-12 text-right pr-0"> <input type="file" id="excel-file" accept=".xlsx, .xls" placeholder="Tải lên file excel">-->
                                <!--</div>-->
                            </div>
                        </div>
                        <div class="table-responsive h-200">
                            <table id="excel-table" class="table table-bordered mb-2 table-striped display"></table>
                            <table id="tbData" class="table table-bordered mb-2 table-striped">
                                <thead class="bg-primary text-white ">
                                    <tr>
                                        <th scope="col">STT</th>
                                        <th scope="col">Tên nhân viên</th>
                                        <th scope="col">Tên sản phẩm</th>
                                        <th scope="col">Giờ nấu</th>
                                        <th scope="col">Giờ hết hạn</th>
                                        <th scope="col">Số lượng</th>
                                    </tr>
                                </thead>
                                <tbody> <?php
                                $item = "";
                                foreach ($banthanhpham as $product) {
                                    $item .= '  <option value="' . $product->id . '" data-cooking="' . $product->cooking_time . '">' . $product->name_product . '</option>';
                                }

                                for ($i = 0; $i < 20; $i++) {
                                    $select = '<select id="product' . $i . '" class="form-control" onchange="selectBTP(' . $i . ')">
                                                <option selected value="">--chọn sản phẩm--</option>' . $item . '
                                            </select>';
                                    echo '<tr>' .
                                        '<td class="text-center">' . ($i + 1) . '</td>' .
                                        '<td><input type="text" id="name_nv' . $i . '" class="form-control" name="namenv" placeholder="Tên nhân viên"></td>' .
                                        '<td>' .
                                        $select .
                                        '</td>' .
                                        '<td><input type="text" id="timecook' . $i . '" class="form-control" name="time" placeholder="Giờ nấu" disabled="true"/></td>' .
                                        '<td><input type="text" id="end_timecook' . $i . '" class="form-control" placeholder="Giờ hết hạn" disabled="true"/></td>' .
                                        '<td style="display:flex"><input type="number" id="totalcooke' . $i . '" class="form-control" placeholder="Số lượng" />
                                                    <select name="unit" id="unit' . $i . '" class="form-control" style="width:40%;">
                                                      <option value="1">Gam</option>
                                                      <option value="2">ML</option>
                                                    </select>
                                                </td>' .
                                        '</tr>';
                                }
                                ?> </tbody>
                            </table>
                            <div class="alert alert-warning" id="error">
                                <strong>Warning!</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Modal lịch sử nấu đồ-->
            <div class="modal fade" id="todayModal" tabindex="-1" role="dialog" aria-labelledby="todayModal"
                aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Lịch sử nấu đồ</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body table-reponse">
                            <div>
                                <form class="form-row">
                                    <div class="col-md-4 mb-4">
                                        <label>Ngày bắt đầu</label>
                                        <input type="date" class="form-control" id="start_date" name="start_date"
                                            placeholder="Last name">
                                    </div>
                                    <div class="col-md-4 mb-4">
                                        <label>Ngày kết thúc</label>
                                        <input type="date" class="form-control" id="end_date" name="end_date"
                                            placeholder="Last name" value="">
                                    </div>
                                    <?php if ($storeCode == 'qlcd' || $storeCode == 'daotao' || $storeCode == 'ksnb' || $storeCode == 'tocotocotea') { ?>
                                        <div class="col-md-4 mb-4">
                                            <label>Store</label>
                                            <select class="form-control" id="store" name="store">
                                                <option value="">-- Chọn Store --</option>
                                                <?php foreach ($store as $stores): ?>
                                                    <option value="<?= htmlspecialchars($stores->store_id) ?>">
                                                        <?= htmlspecialchars($stores->store_name) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    <?php } ?>
                                    <div class="col-md-4 mb-4" style="margin:auto">
                                        <input class="form-control btn-primary" type="button" id="submitbtn"
                                            value="Tra cứu"></input>
                                    </div>
                                </form>
                            </div>
                            <div>
                                <button id="btnPrint"
                                    style="margin-bottom: 10px;border: 1px solid #000;font-size: 16px;padding: 5px 15px;">Print</button>
                            </div>
                            <table id="tbToDay" class="table table-bordered mb-2 table-striped display"
                                style="width:100%">
                                <thead class="bg-primary text-white ">
                                    <tr>
                                        <th>ID</th>
                                        <th>Cửa hàng</th>
                                        <th>Tên nhân viên</th>
                                        <th>Tên sản phẩm</th>
                                        <th>Ngày nấu</th>
                                        <th>Giờ nấu</th>
                                        <th>Ngày hết hạn</th>
                                        <th>Giờ hết hạn</th>
                                        <th>Số lượng</th>
                                        <th>Ngày tạo</th>
                                        <th>Nhân viên hủy</th>
                                        <th>Số lượng hủy</th>
                                        <th>Ngày Hủy</th>
                                        <th>Giờ hủy</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Modal lịch sử nấu đồ sum-->
            <div class="modal fade" id="todayModalSum" tabindex="-1" role="dialog" aria-labelledby="todayModalSum"
                aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Tổng hợp lịch sử nấu đồ</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body table-reponse">
                            <div>
                                <form class="form-row">
                                    <div class="col-md-4 mb-4">
                                        <label>Ngày bắt đầu</label>
                                        <input type="date" class="form-control" id="start_date_sum"
                                            placeholder="Last name">
                                    </div>
                                    <div class="col-md-4 mb-4">
                                        <label>Ngày kết thúc</label>
                                        <input type="date" class="form-control" id="end_date_sum"
                                            placeholder="Last name">
                                    </div>
                                    <div class="col-md-4 mb-4" style="margin:auto">
                                        <input class="form-control btn-primary" type="button" id="submitbtn_sum"
                                            value="Tra cứu"></input>
                                    </div>
                                </form>
                            </div>
                            <table id="tbToDaySum" class="table table-bordered mb-2 table-striped display"
                                style="width:100%">
                                <thead class="bg-primary text-white ">
                                    <tr>
                                        <th>ID</th>
                                        <th>Tên sản phẩm</th>
                                        <th>Số lượng</th>
                                        <th>Ngày nấu</th>
                                        <th>Nhân viên hủy</th>
                                        <th>Số lượng hủy</th>
                                        <th>Thời gian hủy</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody_cancel">
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                        </div>
                    </div>
                </div>
            </div>
            <!--modal thông báo hết hạn sản phẩm-->
            <div class="modal fade" id="myModal" role="dialog">
                <div class="modal-dialog">

                    <!-- Modal content-->
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                            <h4 class="modal-title">Thông báo hết hạn bán thành phẩm</h4>
                        </div>
                        <div class="modal-body">
                            <h4 id="btl-end-time">Nội dung</h4>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.1/dist/umd/popper.min.js"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
        <script src="https://kit.fontawesome.com/a57feb326d.js" crossorigin="anonymous"></script>
        <script>
            function selectBTP(id) {
                document.getElementById("timecook" + id).disabled = false;
                //localStorage.setItem("time_cooking",$('#product'+id).find('option:selected').attr('data-cooking'));
                localStorage.setItem("time_cooking_timecook" + id, $('#product' + id).find('option:selected').attr('data-cooking'));
                jQuery("#timecook" + id).val("");
                jQuery("#end_timecook" + id).val("");
            }


            document.getElementById("btnPrint").addEventListener("click", function () {
                const table = document.getElementById("tbToDay");
                const rows = table.querySelectorAll("tbody tr");
                let pagesContent = ""; // Chuỗi chứa dữ liệu
                rows.forEach((row, index) => {
                    const cells = row.querySelectorAll("td");
                    const name = cells[3].innerText; // Tên sản phẩm
                    const time_cooking = cells[5].innerText + ' ' + cells[4].innerText;
                    const time_end = cells[7].innerText + ' ' + cells[6].innerText;
                    const total = cells[8].innerText;
                    const date = cells[9].innerText;

                    // Ghép dữ liệu thành dòng
                    // content += `Sản phẩm: ${name}:\n`;
                    // content += `Thời gian nấu: ${time_cooking}\n`;
                    // content += `Thời gian hết hạn: ${time_end}\n`;
                    // content += `Số lượng: ${total}\n`;
                    // content += `Ngày nấu: ${date}\n\n`;
                    pagesContent += `
                    <table style="font-size:9px;display: flex; text-align:left" class="label">
                      <tr>
                        <th><strong>Sản phẩm: ${name}</strong></th>
                      </tr>
                      <tr>
                      <th><strong>Thời gian nấu: ${time_cooking}</strong></th></tr>
                      <tr><th><strong>Thời gian hết hạn: ${time_end}</strong></th></tr>
                      <tr><th><strong>Số lượng: ${total}</strong></th></tr>
                      <tr><th><strong>Ngày nấu: ${date}</strong></th></tr>
                    </table>
                `;
                });
                // Nội dung cho mỗi trang


                // Mở cửa sổ in và hiển thị dữ liệu theo dòng
                const printWindow = window.open("", "_blank");
                printWindow.document.open();
                printWindow.document.write(`
                <html>
                  <head>
                    <title>In Dữ Liệu</title>
                    <style>
                      @media print {
                        @page {
                          size: 50mm 30mm; /* Kích thước tem nhãn */
                          margin: 0; /* Bỏ lề để vừa tem nhãn */
                        }
                        body {
                          margin: 0;
                          padding: 0;
                        }
                        .label {
                          width: 50mm;
                          height: 30mm;
                          margin: 0;
                          //padding: 5px;
                        //  display: flex;
                          //flex-direction: column;
                          //justify-content: center;
                          //align-items: flex-start;
                          padding-top:10px;
                        }
                        .label th {
                          width: 100%;
                          text-align: left;
                          font-size: 9px;
                        }
                      }
                    </style>
                  </head>
                  <body>
                    <div style="padding: 0 10px;">
                    ${pagesContent}
                    </div>
                  </body>
                </html>
              `);
                printWindow.document.close();
                printWindow.print();
            });
        </script>
</body>

</html>