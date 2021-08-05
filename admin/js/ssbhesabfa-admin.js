jQuery(function ($) {
    $('#syncProductsProgress').hide();
    $('#exportProductsProgress').hide();
    $('#exportCustomersProgress').hide();
    $('#importProductsProgress').hide();
    $('#exportProductsOpeningQuantityProgress').hide();
    $('#updateProductsProgress').hide();
    $('#syncOrdersProgress').hide();

    'use strict';
    $(function () {
        // AJAX - Export Products
        $('#ssbhesabfa_export_products').submit(function () {
            // show processing status
            $('#ssbhesabfa-export-product-submit').attr('disabled', 'disabled');
            $('#ssbhesabfa-export-product-submit').removeClass('button-primary');
            $('#ssbhesabfa-export-product-submit').html('<i class="ofwc-spinner"></i> خروج محصولات...');
            $('#ssbhesabfa-export-product-submit i.spinner').show();

            $('#exportProductsProgress').show();
            $('#exportProductsProgressBar').css('width', 0 + '%').attr('aria-valuenow', 0);

            exportProducts(1, 1, 1, 0);

            return false;
        });
    });

    function exportProducts(batch, totalBatch, total, updateCount) {
        const data = {
            'action': 'adminExportProducts',
            'batch': batch,
            'totalBatch': totalBatch,
            'total': total,
            'updateCount': updateCount
        };
        $.post(ajaxurl, data, function (response) {
            if (response !== 'failed') {
                const res = JSON.parse(response);
                res.batch = parseInt(res.batch);
                if (res.batch < res.totalBatch) {
                    let progress = (res.batch * 100) / res.totalBatch;
                    progress = Math.round(progress);
                    $('#exportProductsProgressBar').css('width', progress + '%').attr('aria-valuenow', progress);
                    exportProducts(res.batch + 1, res.totalBatch, res.total, res.updateCount);
                    return false;
                } else {
                    $('#exportProductsProgressBar').css('width', 100 + '%').attr('aria-valuenow', 100);
                    setTimeout(() => {
                        top.location.replace(res.redirectUrl);
                    }, 1000);
                    return false;
                }
            } else {
                alert('Error exporting products.');
                return false;
            }
        });
    }

    $(function () {
        // AJAX - Import Products
        $('#ssbhesabfa_import_products').submit(function () {
            // show processing status
            $('#ssbhesabfa-import-product-submit').attr('disabled', 'disabled');
            $('#ssbhesabfa-import-product-submit').removeClass('button-primary');
            $('#ssbhesabfa-import-product-submit').html('<i class="ofwc-spinner"></i> در حال ورود کالاها از حسابفا, لطفاً صبر کنید...');
            $('#ssbhesabfa-import-product-submit i.spinner').show();

            $('#importProductsProgress').show();
            $('#importProductsProgressBar').css('width', 0 + '%').attr('aria-valuenow', 0);

            importProducts(1, 1, 1, 0);

            return false;
        });
    });

    function importProducts(batch, totalBatch, total, updateCount) {
        var data = {
            'action': 'adminImportProducts',
            'batch': batch,
            'totalBatch': totalBatch,
            'total': total,
            'updateCount': updateCount
        };
        $.post(ajaxurl, data, function (response) {
            if ('failed' !== response) {
                const res = JSON.parse(response);
                res.batch = parseInt(res.batch);
                if (res.batch < res.totalBatch) {
                    let progress = (res.batch * 100) / res.totalBatch;
                    progress = Math.round(progress);
                    $('#importProductsProgressBar').css('width', progress + '%').attr('aria-valuenow', progress);
                    //alert('batch: ' + res.batch + ', totalBatch: ' + res.totalBatch + ', total: ' + res.total);
                    importProducts(res.batch + 1, res.totalBatch, res.total, res.updateCount);
                    return false;
                } else {
                    $('#importProductsProgressBar').css('width', 100 + '%').attr('aria-valuenow', 100);
                    setTimeout(() => {
                        top.location.replace(res.redirectUrl);
                    }, 1000);
                    return false;
                }
            } else {
                alert('Error importing products.');
                return false;
            }
        });
    }

    $(function () {
        // AJAX - Export Products opening quantity
        $('#ssbhesabfa_export_products_opening_quantity').submit(function () {
            // show processing status
            $('#ssbhesabfa-export-product-opening-quantity-submit').attr('disabled', 'disabled');
            $('#ssbhesabfa-export-product-opening-quantity-submit').removeClass('button-primary');
            $('#ssbhesabfa-export-product-opening-quantity-submit').html('<i class="ofwc-spinner"></i> استخراج موجودی اول دوره...');
            $('#ssbhesabfa-export-product-opening-quantity-submit i.spinner').show();

            $('#exportProductsOpeningQuantityProgress').show();
            $('#exportProductsOpeningQuantityProgressBar').css('width', 0 + '%').attr('aria-valuenow', 0);

            exportProductsOpeningQuantity(1, 1, 1);

            return false;
        });
    });

    function exportProductsOpeningQuantity(batch, totalBatch, total) {
        var data = {
            'action': 'adminExportProductsOpeningQuantity',
            'batch': batch,
            'totalBatch': totalBatch,
            'total': total
        };
        $.post(ajaxurl, data, function (response) {
            if ('failed' !== response) {
                const res = JSON.parse(response);
                res.batch = parseInt(res.batch);
                if (res.batch < res.totalBatch) {
                    let progress = (res.batch * 100) / res.totalBatch;
                    progress = Math.round(progress);
                    $('#exportProductsOpeningQuantityProgressBar').css('width', progress + '%').attr('aria-valuenow', progress);
                    exportProductsOpeningQuantity(res.batch + 1, res.totalBatch, res.total);
                    return false;
                } else {
                    $('#exportProductsOpeningQuantityProgressBar').css('width', 100 + '%').attr('aria-valuenow', 100);
                    setTimeout(() => {
                        top.location.replace(res.redirectUrl);
                    }, 1000);
                    return false;
                }
            } else {
                alert('Error exporting products opening quantity.');
                return false;
            }
        });
    }

    $(function () {
        // AJAX - Export Customers
        $('#ssbhesabfa_export_customers').submit(function () {
            // show processing status
            $('#ssbhesabfa-export-customer-submit').attr('disabled', 'disabled');
            $('#ssbhesabfa-export-customer-submit').removeClass('button-primary');
            $('#ssbhesabfa-export-customer-submit').html('<i class="ofwc-spinner"></i> خروجی مشتریان، لطفاً صبر کنید...');
            $('#ssbhesabfa-export-customer-submit i.spinner').show();

            $('#exportCustomersProgress').show();
            $('#exportCustomersProgressBar').css('width', 0 + '%').attr('aria-valuenow', 0);

            exportCustomers(1, 1, 1, 0);

            return false;
        });
    });

    function exportCustomers(batch, totalBatch, total, updateCount) {
        const data = {
            'action': 'adminExportCustomers',
            'batch': batch,
            'totalBatch': totalBatch,
            'total': total,
            'updateCount': updateCount
        };
        $.post(ajaxurl, data, function (response) {
            if (response !== 'failed') {
                const res = JSON.parse(response);
                res.batch = parseInt(res.batch);
                if (res.batch < res.totalBatch) {
                    let progress = (res.batch * 100) / res.totalBatch;
                    progress = Math.round(progress);
                    $('#exportCustomersProgressBar').css('width', progress + '%').attr('aria-valuenow', progress);
                    exportCustomers(res.batch + 1, res.totalBatch, res.total, res.updateCount);
                    return false;
                } else {
                    $('#exportCustomersProgressBar').css('width', 100 + '%').attr('aria-valuenow', 100);
                    setTimeout(() => {
                        top.location.replace(res.redirectUrl);
                    }, 1000);
                    return false;
                }
            } else {
                alert('Error exporting customers.');
                return false;
            }
        });
    }

    $(function () {
        // AJAX - Sync Changes
        $('#ssbhesabfa_sync_changes').submit(function () {
            // show processing status
            $('#ssbhesabfa-sync-changes-submit').attr('disabled', 'disabled');
            $('#ssbhesabfa-sync-changes-submit').removeClass('button-primary');
            $('#ssbhesabfa-sync-changes-submit').html('<i class="ofwc-spinner"></i> همسان سازی تغییرات...');
            $('#ssbhesabfa-sync-changes-submit i.spinner').show();

            var data = {
                'action': 'adminSyncChanges'
            };

            // post it
            $.post(ajaxurl, data, function (response) {
                if ('failed' !== response) {
                    var redirectUrl = response;

                    /** Debug **/
                    // console.log(redirectUrl);
                    // return false;

                    top.location.replace(redirectUrl);
                    return false;
                } else {
                    alert('Error syncing changes.');
                    return false;
                }
            });
            /*End Post*/
            return false;
        });
    });

    $(function () {
        // AJAX - Sync Products
        $('#ssbhesabfa_sync_products').submit(function () {

            // show processing status
            $('#ssbhesabfa-sync-products-submit').attr('disabled', 'disabled');
            $('#ssbhesabfa-sync-products-submit').removeClass('button-primary');
            $('#ssbhesabfa-sync-products-submit').html('<i class="ofwc-spinner"></i> همسان سازی محصولات...');
            $('#ssbhesabfa-sync-products-submit i.spinner').show();

            $('#syncProductsProgress').show();
            $('#syncProductsProgressBar').css('width', 0 + '%').attr('aria-valuenow', 0);

            syncProducts(1, 1, 1);

            return false;
        });
    });

    function syncProducts(batch, totalBatch, total) {
        const data = {
            'action': 'adminSyncProducts',
            'batch': batch,
            'totalBatch': totalBatch,
            'total': total
        };
        $.post(ajaxurl, data, function (response) {
            if (response !== 'failed') {
                const res = JSON.parse(response);
                res.batch = parseInt(res.batch);
                if (res.batch < res.totalBatch) {
                    let progress = (res.batch * 100) / res.totalBatch;
                    progress = Math.round(progress);
                    $('#syncProductsProgressBar').css('width', progress + '%').attr('aria-valuenow', progress);
                    //alert('batch: ' + res.batch + ', totalBatch: ' + res.totalBatch + ', total: ' + res.total);
                    syncProducts(res.batch + 1, res.totalBatch, res.total);
                    return false;
                } else {
                    $('#syncProductsProgressBar').css('width', 100 + '%').attr('aria-valuenow', 100);
                    setTimeout(() => {
                        top.location.replace(res.redirectUrl);
                    }, 1000);
                    return false;
                }
            } else {
                alert('Error syncing products.');
                return false;
            }
        });
    }

    $(function () {
        // AJAX - Sync Orders
        $('#ssbhesabfa_sync_orders').submit(function () {
            // show processing status
            $('#ssbhesabfa-sync-orders-submit').attr('disabled', 'disabled');
            $('#ssbhesabfa-sync-orders-submit').removeClass('button-primary');
            $('#ssbhesabfa-sync-orders-submit').html('<i class="ofwc-spinner"></i> همسان سازی سفارشات...');
            $('#ssbhesabfa-sync-orders-submit i.spinner').show();

            $('#syncOrdersProgress').show();
            $('#syncOrdersProgressBar').css('width', 0 + '%').attr('aria-valuenow', 0);

            syncOrders(1, 1, 1, 0);

            return false;
        });
    });

    function syncOrders(batch, totalBatch, total, updateCount) {
        var date = $('#ssbhesabfa_sync_order_date').val();

        const data = {
            'action': 'adminSyncOrders',
            'date': date,
            'batch': batch,
            'totalBatch': totalBatch,
            'total': total,
            'updateCount': updateCount
        };
        $.post(ajaxurl, data, function (response) {
            if (response !== 'failed') {
                const res = JSON.parse(response);
                res.batch = parseInt(res.batch);
                if (res.batch < res.totalBatch) {
                    let progress = (res.batch * 100) / res.totalBatch;
                    progress = Math.round(progress);
                    $('#syncOrdersProgressBar').css('width', progress + '%').attr('aria-valuenow', progress);
                    syncOrders(res.batch + 1, res.totalBatch, res.total, res.updateCount);
                    return false;
                } else {
                    $('#syncOrdersProgressBar').css('width', 100 + '%').attr('aria-valuenow', 100);
                    setTimeout(() => {
                        top.location.replace(res.redirectUrl);
                    }, 1000);
                    return false;
                }
            } else {
                alert('Error syncing orders.');
                return false;
            }
        });
    }

    $(function () {
        // AJAX - Sync Products
        $('#ssbhesabfa_update_products').submit(function () {
            // show processing status
            $('#ssbhesabfa-update-products-submit').attr('disabled', 'disabled');
            $('#ssbhesabfa-update-products-submit').removeClass('button-primary');
            $('#ssbhesabfa-update-products-submit').html('<i class="ofwc-spinner"></i> بروزرسانی محصولات...');
            $('#ssbhesabfa-update-products-submit i.spinner').show();

            $('#updateProductsProgress').show();
            $('#updateProductsProgressBar').css('width', 0 + '%').attr('aria-valuenow', 0);

            updateProducts(1, 1, 1);

            return false;
        });
    });

    function updateProducts(batch, totalBatch, total) {
        var data = {
            'action': 'adminUpdateProducts',
            'batch': batch,
            'totalBatch': totalBatch,
            'total': total
        };
        $.post(ajaxurl, data, function (response) {
            if ('failed' !== response) {
                const res = JSON.parse(response);
                res.batch = parseInt(res.batch);
                if (res.batch < res.totalBatch) {
                    let progress = (res.batch * 100) / res.totalBatch;
                    progress = Math.round(progress);
                    $('#updateProductsProgressBar').css('width', progress + '%').attr('aria-valuenow', progress);
                    updateProducts(res.batch + 1, res.totalBatch, res.total);
                    return false;
                } else {
                    $('#updateProductsProgressBar').css('width', 100 + '%').attr('aria-valuenow', 100);
                    setTimeout(() => {
                        top.location.replace(res.redirectUrl);
                    }, 1000);
                    return false;
                }
            } else {
                alert('Error updating products.');
                return false;
            }
        });
    }

    $(function () {
        // AJAX - Clean log
        $('#ssbhesabfa_clean_log').submit(function () {
            // show processing status
            $('#ssbhesabfa-log-clean-submit').attr('disabled', 'disabled');
            $('#ssbhesabfa-log-clean-submit').removeClass('button-primary');
            $('#ssbhesabfa-log-clean-submit').html('<i class="ofwc-spinner"></i> پاک کردن فایل لاگ، لطفاً صبر کنید...');
            $('#ssbhesabfa-log-clean-submit i.spinner').show();

            var data = {
                'action': 'adminCleanLogFile'
            };

            // post it
            $.post(ajaxurl, data, function (response) {
                if ('failed' !== response) {
                    var redirectUrl = response;

                    /** Debug **/
                    // console.log(redirectUrl);
                    // return false;

                    top.location.replace(redirectUrl);
                    return false;
                } else {
                    alert('Error cleaning log file.');
                    return false;
                }
            });
            /*End Post*/
            return false;
        });
    });

    $(function () {
        // AJAX - Sync Products Manually
        $('#ssbhesabfa_sync_products_manually').submit(function () {
            // show processing status
            $('#ssbhesabfa_sync_products_manually-submit').attr('disabled', 'disabled');
            $('#ssbhesabfa_sync_products_manually-submit').removeClass('button-primary');
            $('#ssbhesabfa_sync_products_manually-submit').html('<i class="ofwc-spinner"></i> ذخیره کردن اطلاعات...');
            $('#ssbhesabfa_sync_products_manually i.spinner').show();

            const inputArray = [];
            const inputs = $('.code-input');
            console.log(inputs);
            for (var n = 0; n < inputs.length; n++) {
                var i = inputs[n];
                console.log(i);
                const obj = {
                    id: $(i).attr('id'),
                    hesabfa_id: $(i).val(),
                    parent_id: $(i).attr('data-parent-id')
                }
                inputArray.push(obj);
            }

            const page = $('#pageNumber').val();
            const rpp = $('#goToPage').attr('data-rpp');

            var data = {
                'action': 'adminSyncProductsManually',
                'data': JSON.stringify(inputArray),
                'page': page,
                'rpp': rpp
            };

            // post it
            $.post(ajaxurl, data, function (response) {
                if ('failed' !== response) {
                    var redirectUrl = response;

                    /** Debug **/
                    // console.log(redirectUrl);
                    // return false;

                    top.location.replace(redirectUrl);
                    return false;
                } else {
                    alert('Error saving data.');
                    return false;
                }
            });
            /*End Post*/
            return false;
        });

        $("#goToPage").click(function () {
            const page = $('#pageNumber').val();
            const rpp = $('#goToPage').attr('data-rpp');
            window.location.href = "?page=hesabfa-sync-products-manually&p=" + page + "&rpp=" + rpp;
        });

        $("#show-tips-btn").click(function () {
            $('#tips-alert').removeClass('d-none');
            $('#tips-alert').addClass('d-block');
        });

        $("#hide-tips-btn").click(function () {
            $('#tips-alert').removeClass('d-block');
            $('#tips-alert').addClass('d-none');
        });
    });

    $(".btn-submit-invoice").on( "click", function() {
        var orderId = $(this).attr("data-order-id");

        var btnEl = $('.btn-submit-invoice[data-order-id=' + orderId + ']');

        btnEl.attr('aria-disabled', true);
        btnEl.addClass('disabled');
        btnEl.html('ثبت فاکتور...');
        //btnEl.show();

        submitInvoice(orderId);
    });

    function submitInvoice(orderId) {
        var data = {
            'action': 'adminSubmitInvoice',
            'orderId': orderId
        };
        $.post(ajaxurl, data, function (response) {
            if ('failed' !== response) {
                const res = JSON.parse(response);
                // refresh page
                location.reload();
            } else {
                alert('Error submiting invoice.');
                return false;
            }
        });
    }

    // change business warning
    var oldApiKey = '';
    $("#changeBusinessWarning").hide();

    $("#ssbhesabfa_account_api").focusin( function () {
        oldApiKey = $("#ssbhesabfa_account_api" ).val();
    });
    $("#ssbhesabfa_account_api").focusout( function () {
        var newApiKey = $("#ssbhesabfa_account_api" ).val();
        if(oldApiKey != '' && oldApiKey != newApiKey) {
            $("#changeBusinessWarning").show();
        }
    });


    $(function () {
        // AJAX - clear all plugin data
        $('#hesabfa-clear-plugin-data').click(function () {
            if (confirm('هشدار: با انجام این عملیات کلیه اطلاعات افزونه شامل روابط بین کالاها، مشتریان و فاکتور ها و همینطور تنظیمات افزونه حذف می گردد.' +
                'آیا از انجام این عملیات مطمئن هستید؟')) {
                $('#hesabfa-clear-plugin-data').addClass('disabled');
                $('#hesabfa-clear-plugin-data').html('حذف دیتای افزونه...');
                var data = {
                    'action': 'adminClearPluginData'
                };
                $.post(ajaxurl, data, function (response) {
                    $('#hesabfa-clear-plugin-data').removeClass('disabled');
                    $('#hesabfa-clear-plugin-data').html('حذف دیتای افزونه');
                    if ('failed' !== response) {
                        alert('دیتای افزونه با موفقیت حذف شد.');
                        return false;
                    } else {
                        alert('خطا در هنگام حذف دیتای افزونه.');
                        return false;
                    }
                });
            } else {
                // Do nothing!
            }
            return false;
        });

        $('#hesabfa-install-plugin-data').click(function () {
            if (confirm('با انجام این عملیات جدول افزونه در دیتابیس وردپرس ایجاد' +
                ' و تنظیمات پیش فرض افزونه تنظیم می گردد.' +
                ' آیا از انجام این عملیات مطمئن هستید؟')) {
                $('#hesabfa-install-plugin-data').addClass('disabled');
                $('#hesabfa-install-plugin-data').html('نصب دیتای افزونه...');
                var data = {
                    'action': 'adminInstallPluginData'
                };
                $.post(ajaxurl, data, function (response) {
                    $('#hesabfa-install-plugin-data').removeClass('disabled');
                    $('#hesabfa-install-plugin-data').html('نصب دیتای افزونه');
                    if ('failed' !== response) {
                        alert('دیتای افزونه با موفقیت نصب شد.');
                        return false;
                    } else {
                        alert('خطا در هنگام نصب دیتای افزونه.');
                        return false;
                    }
                });
            } else {
                // Do nothing!
            }
            return false;
        });
    });

    $(function () {
        $(".hesabfa-item-save").on('click', function (){
            const productId = $("#panel_product_data_hesabfa").data('product-id');
            const attributeId = $(this).data('id');
            const code = $("#hesabfa-item-" + attributeId).val();
            var data = {
                'action': 'adminChangeProductCode',
                'productId': productId,
                'attributeId': attributeId,
                'code': code,
            };
            $(this).prop('disabled', true);
            const _this = this;
            $.post(ajaxurl, data, function (response) {
                $(_this).prop('disabled', false);
                if ('failed' !== response) {
                    const res = JSON.parse(response);
                    alert(res.error ? res.message : 'کد کالای متصل با موفقیت تغییر کرد.');
                    return false;
                } else {
                    alert('خطا در هنگام تغییر کد کالای متصل.');
                    return false;
                }
            });
        });
        $(".hesabfa-item-delete-link").on('click', function (){
            const productId = $("#panel_product_data_hesabfa").data('product-id');
            const attributeId = $(this).data('id');
            var data = {
                'action': 'adminDeleteProductLink',
                'productId': productId,
                'attributeId': attributeId
            };
            $(this).prop('disabled', true);
            const _this = this;
            $.post(ajaxurl, data, function (response) {
                $(_this).prop('disabled', false);
                if ('failed' !== response) {
                    const res = JSON.parse(response);
                    $("#hesabfa-item-" + attributeId).val('');
                    alert(res.error ? res.message : 'ارتباط محصول با موفقیت حذف شد.');
                    return false;
                } else {
                    alert('خطا در هنگام حذف ارتباط.');
                    return false;
                }
            });
        });
        $(".hesabfa-item-update").on('click', function (){
            const productId = $("#panel_product_data_hesabfa").data('product-id');
            const attributeId = $(this).data('id');
            var data = {
                'action': 'adminUpdateProduct',
                'productId': productId,
                'attributeId': attributeId
            };
            $(this).prop('disabled', true);
            const _this = this;
            $.post(ajaxurl, data, function (response) {
                $(_this).prop('disabled', false);
                if ('failed' !== response) {
                    const res = JSON.parse(response);
                    if(res.newPrice)
                        $("#hesabfa-item-price-" + attributeId).text(res.newPrice);
                    if(res.newQuantity)
                        $("#hesabfa-item-quantity-" + attributeId).text(res.newQuantity);
                    if(res.error)
                        alert(res.error);
                    return false;
                } else {
                    alert('خطا در هنگام بروزرسانی محصول.');
                    return false;
                }
            });
        });

        $("#hesabfa-item-save-all").on('click', function (){
            const productId = $("#panel_product_data_hesabfa").data('product-id');
            const itemsCode = $(".hesabfa-item-code");
            const itemsData = [];
            for (let i = 0; i < itemsCode.length; i++) {
                const item = itemsCode[i];
                const attributeId = $(item).data('id');
                const code = $(item).val();
                itemsData.push({attributeId: attributeId, code: code});
            }

            var data = {
                'action': 'adminChangeProductsCode',
                'productId': productId,
                'itemsData': itemsData
            };
            $(this).prop('disabled', true);
            const _this = this;
            $.post(ajaxurl, data, function (response) {
                $(_this).prop('disabled', false);
                if ('failed' !== response) {
                    const res = JSON.parse(response);
                    alert(res.error ? res.message : 'کد کالاهای متصل با موفقیت تغییر کرد.');
                    return false;
                } else {
                    alert('خطا در هنگام تغییر کد کالاهای متصل.');
                    return false;
                }
            });
        });
        $("#hesabfa-item-delete-link-all").on('click', function (){
            const productId = $("#panel_product_data_hesabfa").data('product-id');
            var data = {
                'action': 'adminDeleteProductsLink',
                'productId': productId
            };
            $(this).prop('disabled', true);
            const _this = this;
            $.post(ajaxurl, data, function (response) {
                $(_this).prop('disabled', false);
                if ('failed' !== response) {
                    const res = JSON.parse(response);
                    const itemsCode = $(".hesabfa-item-code");
                    for (let i = 0; i < itemsCode.length; i++) {
                        const item = itemsCode[i];
                        $(item).val('');
                    }
                    setTimeout(function (){
                        alert(res.error ? res.message : 'ارتباط محصولات با موفقیت حذف شد.');
                    }, 100);
                    return false;
                } else {
                    alert('خطا در هنگام حذف ارتباط.');
                    return false;
                }
            });
        });
        $("#hesabfa-item-update-all").on('click', function (){
            const productId = $("#panel_product_data_hesabfa").data('product-id');
            var data = {
                'action': 'adminUpdateProductAndVariations',
                'productId': productId
            };
            $(this).prop('disabled', true);
            const _this = this;
            $.post(ajaxurl, data, function (response) {
                $(_this).prop('disabled', false);
                if ('failed' !== response) {
                    const res = JSON.parse(response);
                    if(res.error)
                    {
                        alert(res.message);
                        return false;
                    }
                    for (let i = 0; i < res.newData.length; i++) {
                        if(res.newData[i].newPrice)
                            $("#hesabfa-item-price-" + res.newData[i].attributeId).text(res.newData[i].newPrice);
                        if(res.newData[i].newQuantity)
                            $("#hesabfa-item-quantity-" + res.newData[i].attributeId).text(res.newData[i].newQuantity);
                    }
                    return false;
                } else {
                    alert('خطا در هنگام بروزرسانی محصول.');
                    return false;
                }
            });
        });
    });

});


