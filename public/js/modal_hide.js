$("#modal-default").on("hidden.bs.modal", function() {
  $(this).removeData("bs.modal");
});
