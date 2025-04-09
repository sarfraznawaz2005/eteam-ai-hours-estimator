function doPost(e) {
    const postData = JSON.parse(e.postData.contents);
    const employeeName = postData.employeeName;
    const spreadsheetId = postData.spreadsheetId;
    const requestType = postData.requestType || 'get';
    const attendanceValue = postData.attendanceValue || 'P';
    const currentDate = new Date();
    const currentMonth = currentDate.toLocaleString('default', { month: 'short' });
    const formattedMonth = currentMonth.charAt(0).toUpperCase() + currentMonth.slice(1).toLowerCase();
    const currentDay = currentDate.getDate(); // Get current day
    const dayColumn = currentDay + 3;
    const spreadsheet = SpreadsheetApp.openById(spreadsheetId);
    const sheet = spreadsheet.getSheetByName(formattedMonth); // Use the current month as the sheet name

    // Find the employee's row by searching through the second column
    const employeeColumn = 2; // Employee names are in the second column
    const startRowForSearch = 6; // Assuming data starts from row 6 to avoid header rows
    const secondColumnRange = sheet.getRange(startRowForSearch, employeeColumn, sheet.getLastRow() - startRowForSearch + 1, 1);
    const secondColumnValues = secondColumnRange.getValues();
    let employeeRow = -1;

    for (let i = 0; i < secondColumnValues.length; i++) {
        if (secondColumnValues[i][0] === employeeName) {
            employeeRow = i + startRowForSearch; // Adjust for actual starting index
            break;
        }
    }

    if (employeeRow === -1) {
        return ContentService.createTextOutput(JSON.stringify({
            status: 'error',
            message: 'Employee name not found.'
        }));
    }

    // Mark the attendance for the employee
    if (dayColumn >= 3) { // Check if dayColumn is correctly identified, considering days start from 3rd column

        if (requestType === 'get') {
            return ContentService.createTextOutput(JSON.stringify({
                status: 'success',
                message: sheet.getRange(employeeRow, dayColumn).getValue()
            }));
        }

        sheet.getRange(employeeRow, dayColumn).setValue(attendanceValue);

        // Verify the operation by reading back the value
        const valueSet = sheet.getRange(employeeRow, dayColumn).getValue();

        if (valueSet === attendanceValue) {
            // The value was successfully set, return success message
            return ContentService.createTextOutput(JSON.stringify({
                status: 'success',
                message: 'Attendance Marked Successfully'
            }));
        } else {
            // The value was not set as expected, return an error message
            return ContentService.createTextOutput(JSON.stringify({
                status: 'error',
                message: 'Failed to mark attendance. Please try again.'
            }));
        }

    } else {
        return ContentService.createTextOutput(JSON.stringify({
            status: 'error',
            message: 'Invalid day column.'
        }));
    }

}
