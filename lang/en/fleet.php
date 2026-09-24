<?php

return [
    'fleet' => 'Fleet & Drivers',
    'drivers' => 'Driver Roster',
    'vehicles' => 'Vehicle Fleet',
    'assignments' => 'Driver Assignments',
    'assignment_calendar' => 'Assignment Calendar',
    'duty_letter' => 'Duty Letter',
    'surat_tugas' => 'Operational Duty Letter',
    'duty_letter_subtitle' => 'Travel Order and Driver Assignment Letter',
    'reference_number' => 'Reference Number',
    'issued_date' => 'Issued Date',

    // Driver
    'driver' => 'Driver',
    'driver_name' => 'Driver Name',
    'driver_gender' => 'Gender',
    'gender_male' => 'Male',
    'gender_female' => 'Female',
    'driver_phone' => 'Phone Number',
    'driver_languages' => 'Languages Spoken',
    'driver_status' => 'Driver Status',
    'status_active' => 'Active',
    'status_inactive' => 'Inactive',
    'add_driver' => 'Add Driver',
    'edit_driver' => 'Edit Driver',

    // Vehicle
    'vehicle' => 'Vehicle',
    'vehicle_plate' => 'License Plate',
    'vehicle_type' => 'Vehicle Type',
    'vehicle_capacity' => 'Passenger Capacity',
    'capacity_pax' => ':count Passengers',
    'add_vehicle' => 'Add Vehicle',
    'edit_vehicle' => 'Edit Vehicle',

    // Assignment & Booking
    'booking_code' => 'Booking Code',
    'guest_name' => 'Lead Guest Name',
    'pax_count' => 'Pax Count',
    'departure_date' => 'Departure Date',
    'return_date' => 'Return Date',
    'assignment_period' => 'Assignment Period',
    'assign_driver' => 'Assign Driver',
    'assign_driver_and_vehicle' => 'Assign Driver & Vehicle',
    'change_assignment' => 'Change Assignment',
    'cancel_assignment' => 'Cancel Assignment',
    'cancel_reason' => 'Cancellation Reason',
    'notes' => 'Special Notes',
    'assigned_driver' => 'Assigned Driver',
    'assigned_vehicle' => 'Assigned Vehicle',
    'no_assignment' => 'No driver assigned for this booking yet.',
    'unassigned' => 'Unassigned',

    // Gender preference
    'gender_preference' => 'Driver Gender Preference',
    'pref_female' => 'Female Driver',
    'pref_male' => 'Male Driver',
    'pref_any' => 'No Preference',
    'warning_no_female' => 'Warning: No female driver is available for this booking period in this branch.',
    'warning_no_male' => 'Warning: No male driver is available for this booking period in this branch.',
    'warning_no_driver' => 'Warning: No driver is available for this booking period.',
    'suggested_drivers' => 'Available Driver Suggestions',
    'suggested_badge' => 'Matches Preference & Available',

    // Statuses
    'status_assigned' => 'Assigned',
    'status_in_progress' => 'In Progress',
    'status_completed' => 'Completed',
    'status_cancelled' => 'Cancelled',

    // Duty letter details
    'guest_manifest' => 'Guest Manifest',
    'operational_instructions' => 'Operational Instructions',
    'authorized_by' => 'Authorized By',
    'duty_signature_note' => 'This document is valid and digitally issued by Indogate Travel Platform.',

    // Feedback
    'saved_successfully' => 'Saved successfully.',
    'assigned_successfully' => 'Driver and vehicle assigned successfully.',
    'cancelled_successfully' => 'Assignment cancelled successfully.',
    'deleted_successfully' => 'Deleted successfully.',
    'has_future_assignments' => 'Cannot deactivate/delete: active assignments remain. Cancel them first.',

    // i18n sweep
    'all_gender' => 'All Genders',
    'search_assignment_ph' => 'Search driver, plate, booking…',
    'active_date' => 'Active Date:',
    'assignment_total' => 'Total :count active assignments on this date',
    'cancel_reason_hint' => 'Enter the reason for cancelling this driver assignment. It will be recorded in the audit log.',
    'calendar_lede' => 'Driver and fleet assignment schedule at branch :branch',
    'drivers_lede' => 'Drivers at branch :branch',
    'vehicles_lede' => 'Vehicles at branch :branch',
    'no_vehicle_data' => 'No vehicle data for this filter yet.',
];
