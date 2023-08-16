// Marks the checked emails as refunds
function markRefunds() {
    var checkboxes = document.querySelectorAll('input[name="emailCheckbox"]:checked');
    var emailIds = [];
    var type = "Refund";
    emailIds.push(type);

    checkboxes.forEach(function(checkbox) {
        var row = checkbox.closest('tr');
        var emailId = row.cells[0].innerText;
        emailIds.push(emailId);
    });

    // Set the emailIds array in the hidden input field
    document.getElementById('emailIdsInput').value = JSON.stringify(emailIds);
}

// Marks the checked emails as cancels
function markCancels() {
    var checkboxes = document.querySelectorAll('input[name="emailCheckbox"]:checked');
    var emailIds = [];
    var type = "Cancel";

    emailIds.push(type);
    
    checkboxes.forEach(function(checkbox) {
        var row = checkbox.closest('tr');
        var emailId = row.cells[0].innerText;
        emailIds.push(emailId);
    });

    // Set the emailIds array in the hidden input field
    document.getElementById('emailIdsInput').value = JSON.stringify(emailIds);
}

// Marks the checked emails as pending
function markPending() {
    var checkboxes = document.querySelectorAll('input[name="emailCheckbox"]:checked');
    var emailIds = [];
    var type = "Pending";
    emailIds.push(type);

    checkboxes.forEach(function(checkbox) {
        var row = checkbox.closest('tr');
        var emailId = row.cells[0].innerText;
        emailIds.push(emailId);
    });

    // Set the emailIds array in the hidden input field
    document.getElementById('emailIdsInput').value = JSON.stringify(emailIds);
}

// Marks the checked emails as closed
function markClosed() {
    var checkboxes = document.querySelectorAll('input[name="emailCheckbox"]:checked');
    var emailIds = [];
    var type = "Closed";

    emailIds.push(type);
    
    checkboxes.forEach(function(checkbox) {
        var row = checkbox.closest('tr');
        var emailId = row.cells[0].innerText;
        emailIds.push(emailId);
    });

    // Set the emailIds array in the hidden input field
    document.getElementById('emailIdsInput').value = JSON.stringify(emailIds);
}

