/**
 * AEOX SEO Admin JavaScript
 */
(function($) {
    'use strict';
    var AEOX_Admin = {
        init: function() {
            this.bindEvents();
        },
        bindEvents: function() {
            $(document).on('click', '.aeox-analyze-btn', this.handleAnalyzeClick);
            $(document).on('click', '.aeox-tab-nav li', this.handleTabSwitch);
        },
        handleAnalyzeClick: function(e) {
            e.preventDefault();
            var $button = $(this);
            var postId = $button.data('post-id') || $('#post_ID').val();
            if (!postId) { return; }
            $button.prop('disabled', true).text('Analyzing...');
            $.ajax({
                url: aeoxAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'aeox_analyze_post',
                    nonce: aeoxAdmin.nonce,
                    post_id: parseInt(postId)
                },
                success: function(response) {
                    if (response.success) {
                        alert('Analysis Complete');
                    } else {
                        alert('Analysis Error');
                    }
                },
                error: function() {
                    alert('Analysis Error');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Re-Analyze');
                }
            });
        },
        handleTabSwitch: function(e) {
            e.preventDefault();
            var $tab = $(this);
            var tabId = $tab.data('tab');
            $('.aeox-tab-nav li').removeClass('active');
            $tab.addClass('active');
            $('.aeox-tab-content').hide();
            $('#' + tabId).show();
        }
    };
    $(document).ready(function() {
        AEOX_Admin.init();
    });
})(jQuery);
