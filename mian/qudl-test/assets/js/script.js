$(document).ready(function() {
    $('.btn-test').click(function() {
        const testType = $(this).data('test');
        const $btn = $(this);
        const $spinner = $btn.find('.spinner-border');
        
        $btn.prop('disabled', true);
        $spinner.removeClass('d-none');

        const config = {
            serverUrl: $('#serverUrl').val(),
            version: $('#version').val(),
            clientIp: $('#clientIp').val()
        };

        $.post(`../api/test-${testType}.php`, config)
            .done(response => {
                displayResult(testType, response);
            })
            .fail(xhr => {
                displayResult(testType, {
                    success: false,
                    message: `请求失败: ${xhr.statusText}`,
                    details: xhr.responseText
                });
            })
            .always(() => {
                $btn.prop('disabled', false);
                $spinner.addClass('d-none');
            });
    });

    function displayResult(testType, data) {
        const title = {
            manifest: '清单接口测试',
            download: '文件下载测试',
            security: '安全验证测试'
        }[testType];

        const resultHtml = `
            <div class="test-result ${data.success ? 'success' : 'failure'}">
                <h5>${title} - ${data.success ? '✓ 成功' : '✗ 失败'}</h5>
                <div class="log-timestamp">${new Date().toLocaleString()}</div>
                <div class="mt-2">${data.message}</div>
                ${data.details ? `<pre class="mt-2">${data.details}</pre>` : ''}
            </div>
        `;

        $('#resultPanel .list-group').prepend(resultHtml);
    }
});