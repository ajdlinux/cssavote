$(function() {
    $('.candidates_unselected').each(function() {
            $(this).sortable({
                connectWith: '#s' + $(this).attr('id').slice(1),
                placeholder: "ui-state-highlight",
                dropOnEmpty: true
            });
    });
    $('.candidates_selected').each(function() {
            $(this).sortable({
                connectWith: '#u' + $(this).attr('id').slice(1),
                placeholder: "ui-state-highlight",
                dropOnEmpty: true
            });
    });
});

function submit_vote() {
    var post_data = {};
    var do_submit = true;
    $('.candidates_selected').each(function() {
        post_data[Number($(this).attr('id').slice(1))] = new Array();
        var pref = 0;
        $('li', this).each(function() {
            post_data[Number($(this).parent().attr('id').slice(1))][pref] = Number($(this).attr('id').slice(1));
            //alert('Election ' + $(this).parents('.election').attr('id').slice(1) + ' - Preference ' + pref + ' - ' + $(this).text());
            pref++;
        });
        var required_preferences = $(this).parent().parent().children('.election_num_pos_hidden').text();
        var actual_preferences = post_data[Number($(this).parent().parent().attr('id').slice(1))].length;
        var election_title = $(this).parent().parent().children('.election_title_hidden').text();
        if (actual_preferences < required_preferences) {
            if (!confirm('You have only provided ' +
                actual_preferences + ' preferences for ' + election_title +
                '.\n\nYou must provide at least ' + required_preferences +
                ' preferences for your vote to count.\n\nClick Cancel to return to the voting screen, or click OK if you wish to cast an informal vote.')) {
                do_submit = false;
                return false;
            }
        }
                
    });
    
    //alert(JSON.stringify(post_data));
    if (do_submit) {
        $('#votestring').val(JSON.stringify(post_data));
        $('#voteform').submit();
    }
    
    return false;
}