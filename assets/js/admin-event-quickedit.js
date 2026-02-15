jQuery(function($){
  // Extender inlineEdit para rellenar el selector "Pare"
  var $wp_inline_edit = inlineEditPost.edit;

  inlineEditPost.edit = function( id ) {
    $wp_inline_edit.apply( this, arguments );

    var postId = 0;
    if ( typeof(id) === 'object' ) {
      postId = parseInt( this.getId(id), 10 );
    } else {
      postId = parseInt( id, 10 );
    }
    if ( !postId ) return;

    var $row = $('#post-' + postId);
    var parentId = 0;

    var $label = $row.find('.fcsd-event-parent-label').first();
    if ($label.length) {
      parentId = parseInt($label.data('parent-id'), 10) || 0;
    }

    var $editRow = $('#edit-' + postId);
    $editRow.find('select.fcsd-event-parent-select').val(String(parentId));
  };
});
