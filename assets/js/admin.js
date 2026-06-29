// Admin JavaScript Functions
// Ensure jQuery and SweetAlert2 are loaded
if (typeof jQuery === 'undefined') {
    console.error('jQuery not loaded. Please ensure jQuery is included before this script.');
}
if (typeof Swal === 'undefined') {
    console.warn('SweetAlert2 not loaded. Some features may not work.');
}

$(document).ready(function() {
    // Debug: Check if forms exist
    console.log('addTeamForm exists:', $('#addTeamForm').length > 0);
    console.log('Swal available:', typeof Swal !== 'undefined');
    
    // Add Team
    $('#addTeamForm').on('submit', function(e) {
        e.preventDefault();
        console.log('Add team form submitted');
        var formData = $(this).serialize();
        console.log('Form data:', formData);
        
        $.ajax({
            url: '../ajax/teams.php?action=create',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(data) {
                console.log('Add team response:', data);
                if (data.success) {
                    Swal.fire('Success', data.message, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Add team error:', status, error, xhr.responseText);
                Swal.fire('Error', 'An error occurred: ' + error, 'error');
            }
        });
    });

    // Edit Team Button
    $(document).on('click', '.btn-edit-team', function() {
        var teamId = $(this).data('team-id');
        $.ajax({
            url: '../ajax/teams.php?action=edit&id=' + teamId,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data) {
                    var team = response.data;
                    $('#team_id').val(team.id);
                    $('#edit_school_name').val(team.school_name);
                    $('#edit_team_name').val(team.team_name);
                    $('#edit_leader_name').val(team.leader_name);
                    $('#edit_email').val(team.email);
                    var editModal = new bootstrap.Modal(document.getElementById('editTeamModal'));
                    editModal.show();
                } else {
                    Swal.fire('Error', 'Failed to load team data', 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Failed to load team data', 'error');
            }
        });
    });

    // Edit Team Form Submit
    $('#editTeamForm').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        $.ajax({
            url: '../ajax/teams.php?action=update',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('editTeamModal')).hide();
                    showTeamAlert('success', data.message);
                    setTimeout(function() {
                        location.reload();
                    }, 1800);
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'An error occurred', 'error');
            }
        });
    });

    // Reset Password Button
    $(document).on('click', '.btn-reset-password', function() {
        var teamId = $(this).data('team-id');
        $('#password_team_id').val(teamId);
        $('#new_password').val('');
        var resetModal = new bootstrap.Modal(document.getElementById('resetPasswordModal'));
        resetModal.show();
    });

    // Reset Password Form Submit
    $('#resetPasswordForm').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        $.ajax({
            url: '../ajax/teams.php?action=reset_password',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('resetPasswordModal')).hide();
                    showTeamAlert('success', data.message);
                    setTimeout(function() {
                        location.reload();
                    }, 1800);
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'An error occurred', 'error');
            }
        });
    });

    // Delete Team Button
    $(document).on('click', '.btn-delete-team', function() {
        var teamId = $(this).data('team-id');
        Swal.fire({
            title: 'Delete Team?',
            text: 'This action cannot be undone',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../ajax/teams.php?action=delete',
                    type: 'POST',
                    data: { id: teamId },
                    dataType: 'json',
                    success: function(data) {
                        if (data.success) {
                            Swal.fire('Deleted!', data.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    }
                });
            }
        });
    });

    // Bulk Delete Selected Teams
    $(document).on('change', '#selectAllTeams', function() {
        $('.team-checkbox').prop('checked', $(this).is(':checked'));
        updateSelectedCount();
    });

    $(document).on('change', '.team-checkbox', function() {
        var allChecked = $('.team-checkbox').length === $('.team-checkbox:checked').length;
        $('#selectAllTeams').prop('checked', allChecked);
        updateSelectedCount();
    });

    function updateSelectedCount() {
        var selectedCount = $('.team-checkbox:checked').length;
        $('#selectedCount').text(selectedCount + ' selected');
        $('#deleteSelectedBtn').prop('disabled', selectedCount === 0);
    }

    $('#deleteSelectedBtn').on('click', function() {
        var selectedIds = $('.team-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        if (selectedIds.length === 0) {
            return;
        }

        Swal.fire({
            title: 'Delete selected teams?',
            text: 'This action cannot be undone',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete selected'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../ajax/teams.php?action=delete',
                    type: 'POST',
                    data: { 'ids[]': selectedIds },
                    dataType: 'json',
                    success: function(data) {
                        if (data.success) {
                            Swal.fire('Deleted!', data.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Failed to delete selected teams', 'error');
                    }
                });
            }
        });
    });

    updateSelectedCount();

    function showTeamAlert(type, message) {
        var alertBox = $('#teamAlert');
        alertBox.removeClass('d-none alert-success alert-danger alert-warning alert-info')
            .addClass('alert-' + type + ' page-alert')
            .html('<div class="alert-message">' + message + '</div>');

        setTimeout(function() {
            alertBox.addClass('d-none');
        }, 6000);
    }

    // Add Round
    $('#addRoundForm').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        
        $.ajax({
            url: '../ajax/rounds.php?action=create',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    Swal.fire('Success', data.message, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            }
        });
    });

    // Activate Round
    $(document).on('click', '.btn-activate-round', function() {
        var roundId = $(this).data('round-id');
        $.ajax({
            url: '../ajax/rounds.php?action=activate',
            type: 'POST',
            data: { id: roundId },
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    Swal.fire('Success', data.message, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Failed to activate round', 'error');
            }
        });
    });

    // Deactivate Round
    $(document).on('click', '.btn-deactivate-round', function() {
        var roundId = $(this).data('round-id');
        $.ajax({
            url: '../ajax/rounds.php?action=deactivate',
            type: 'POST',
            data: { id: roundId },
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    Swal.fire('Success', data.message, 'success').then(() => {
                        location.reload();
                    });
                }
            }
        });
    });

    // Delete Round
    $(document).on('click', '.btn-delete-round', function() {
        var roundId = $(this).data('round-id');
        Swal.fire({
            title: 'Delete Round?',
            text: 'This will also delete all questions in this round',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../ajax/rounds.php?action=delete',
                    type: 'POST',
                    data: { id: roundId },
                    dataType: 'json',
                    success: function(data) {
                        if (data.success) {
                            Swal.fire('Deleted!', data.message, 'success').then(() => {
                                location.reload();
                            });
                        }
                    }
                });
            }
        });
    });
});
