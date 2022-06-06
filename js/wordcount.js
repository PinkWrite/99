counter = function() {
    var value = $('#writingArea').val();

    if (value.length == 0) {
        $('#wordCount').html(0);
        $('#wordCountInput').val(0);
        return;
    }

    var regex = /\s+/gi;
    var wordCount = value.trim().replace(/\u2013|\u2014/g, ' ').replace(regex, ' ').split(' ').length;

    $('#wordCount').html(wordCount);
    $('#wordCountInput').val(wordCount);
};

$(document).ready(function() {
//    $('#count').click(counter); // Delete if still works
    $('#writingArea').change(counter);
    $('#writingArea').keydown(counter);
    $('#writingArea').keypress(counter);
    $('#writingArea').keyup(counter);
    $('#writingArea').blur(counter);
    $('#writingArea').focus(counter);
    $(document).ready(counter); // If has words, but no changes
});
