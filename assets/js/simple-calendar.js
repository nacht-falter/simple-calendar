function toggleTimeInputs() {
  var allDayCheckbox = document.getElementById("all_day");
  var startTimeInput = document.getElementById("start_time");
  var endTimeInput = document.getElementById("end_time");

  if (allDayCheckbox && startTimeInput && endTimeInput) {
    var startValue = startTimeInput.value;
    var endValue = endTimeInput.value;

    if (allDayCheckbox.checked) {
      // Change to date inputs
      startTimeInput.type = "date";
      endTimeInput.type = "date";

      // Keep the part (YYYY-MM-DD)
      startTimeInput.value = startValue.split("T")[0];
      endTimeInput.value = endValue.split("T")[0];
    } else {
      // Change back to datetime-local inputs
      startTimeInput.type = "datetime-local";
      endTimeInput.type = "datetime-local";

      // If the value is just a date (from the date input), add default time
      if (startValue && startValue.length === 10) {
        startTimeInput.value = startValue + "T00:00";
      }

      if (endValue && endValue.length === 10) {
        endTimeInput.value = endValue + "T00:00";
      }
    }
  }
}

document.addEventListener("DOMContentLoaded", function () {
  if (document.getElementById("all_day")) {
    toggleTimeInputs();
  }

  if (document.getElementById("sc-event-table")) {
    new DataTable("#sc-event-table", {
      paging: true,
      searching: true,
      ordering: true,
      responsive: true,
      order: [[1, "desc"]],
      pageLength: 10,
      lengthMenu: [10, 25, 50, 100],
      columnDefs: [
        { targets: 8, orderable: false }, // Disable sorting for actions column
      ],
    });
  }
});
