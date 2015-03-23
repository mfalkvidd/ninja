(function () {

  var baseurl = _site_domain + _index_page + '/';

  function restart () {
    $.get(baseurl + 'backup/restart')
      .done(function (message) {
        Notify.message(message);
      })
      .fail(function (data) {
        data = JSON.parse(data);
        var notification = Notify.message(data.message, {
          sticky: true,
          buttons: {
            "Show output": function () {
              notification.remove(1);
              Notify.message(data.debug.join('<br>'), {
                sticky: true
              });
            }
          }
        });
      })
  }

  function backup () {

    var notification = Notify.message('<img src="/ninja/application/media/images/loading.gif" /> Creating new backup..', {
      sticky: true,
      removable: false
    });

    $.get(baseurl + 'backup/backup')
      .done(function (file) {

        var first = $('#backups tbody tr:first');

        notification.remove(1);
        Notify.message("Backup  '" + file + "' was created!");

        first.before(
          $('<tr>').append(
            $('<td>').append(
              $('<a class="view" style="border: 0px; margin-right: 4px">')
                .attr('href', baseurl + 'backup/view/' + file)
                .html('<span class="icon-16 x16-backup-view"></span>'),
              $('<a class="restore" style="border: 0px; margin-right: 4px">')
                .attr('href', baseurl + 'backup/restore/' + file)
                .html('<span class="icon-16 x16-backup-restore"></span>'),
              $('<a class="delete" style="border: 0px; margin-right: 4px">')
                .attr('href', baseurl + 'backup/delete/' + file)
                .html('<span class="icon-16 x16-backup-delete"></span>')
            ),
            $('<td>').append(
              $('<a class="download">')
                .attr('href', baseurl + 'backup/download/' + file)
                .text(file)
            )
          ).addClass(first.hasClass('odd') ? 'even' : 'odd')
        );

      })
      .fail(function (data) {
        notification.remove(1);
        data = JSON.parse(data.responseText);
        notification = Notify.message(data.message, {
          type: "error",
          sticky: true,
          buttons: {
            "Show output": function () {
              notification.remove(1);
              Notify.message(data.debug.join('<br>'), {sticky: true});
            }
          }
        });
      });

  }

  $('#verify').live('click', function(){

    var link = $(this);

    $.get($(link).attr('href'))
      .done(function (data) {
        var notification = Notify.message(
        data + '. Do you really want to backup your current configuration?',
        {
          sticky: true,
          buttons: {
            "Yes": function () {
              notification.remove(1);
              backup();
            }
          }
        });
      })
      .fail(function (data) {
        data = JSON.parse(data.responseText);
        var notification = Notify.message(data.message, {
          type: "error",
          sticky: true,
          buttons: {
            "Show output": function () {
              notification.remove(1);
              Notify.message(data.debug.join('<br>'), {sticky: true});
            },
            "Backup anyway": backup
          }
        });
      });

    return false;

  });

  $('a.restore').live('click', function(ev){

    var link = $(this);
    var notification = Notify.message('Do you really want to restore this backup?', {
      sticky: true,
      buttons: {
        "Yes": function () {
          notification.remove(1);
          $.get(link.attr('href'))
            .done(function (data) {
              notification = Notify.message(data + '. Your new configuration will not be used until the monitoring process is restarted', {
                sticky: true,
                buttons: {
                  "Restart now": function () {
                    notification.remove(1);
                    restart();
                  }
                }
              });
            })
            .fail(function (data) {
              data = JSON.parse(data.responseText);
              Notify.message(data.message, {
                type: "error",
                sticky: true,
                "Show output": function () {
                  notification.remove(1);
                  Notify.message(data.debug.join('<br>'), {sticky: true});
                }
              });
            });
        }
      }
    });

    return false;

  });

  $('a.delete').live('click', function(){

    var link = $(this);
    var notification = Notify.message(
      'Do you really want to delete ' + $(link).closest('tr').find('.download').text() + ' ?',
      {
        sticky: true,
        buttons: {
          "Yes": function () {
            $.get($(link).attr('href'))
              .done(function (data) {
                notification.remove(1);
                Notify.message(data);
                link.parents('tr').remove();
              })
              .fail(function (data) {
                notification.remove(1);
                data = JSON.parse(data.responseText);
                Notify.message(data.message, {
                  type: "error"
                });
              })
          }
        }
      });

    return false;

  });

})();