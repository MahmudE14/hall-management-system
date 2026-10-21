# Spec Delta

## Purpose

Defines the private complaint lifecycle from approved-student submission through assignment, collaboration, resolution, closure, and time-limited reopening.

## ADDED Requirements

### Requirement: Approved-student complaint submission
The system SHALL allow an approved student to submit a complaint with category, title, description, and an optional JPG or PNG image no larger than 2 MB.

#### Scenario: Resident submits a valid complaint
- **WHEN** an approved Resident submits valid complaint details
- **THEN** the complaint is created with the student's current room filled automatically

#### Scenario: Attached student submits a valid complaint
- **WHEN** an approved Attached student submits valid complaint details and a location
- **THEN** the complaint is created with the supplied location

#### Scenario: Invalid complaint image
- **WHEN** a student uploads an executable, unsupported type, or image larger than 2 MB
- **THEN** the system rejects the submission and stores no uploaded file

### Requirement: Complaint categories
The system SHALL support Water, Electricity, Internet, Cleanliness, Furniture, Security, and Other complaint categories.

#### Scenario: Filter complaints by category
- **WHEN** an authorized viewer filters complaints by a supported category
- **THEN** only complaints in that category are returned

### Requirement: Unique annual tracking identifier
The system SHALL assign each complaint a unique `KJH-YYYY-NNNN` tracking identifier whose numeric sequence restarts each year and remains unique under simultaneous submissions.

#### Scenario: First complaint of a new year
- **WHEN** the first complaint for a calendar year is created
- **THEN** its tracking identifier ends in `0001` and contains that year

#### Scenario: Simultaneous submissions
- **WHEN** complaints are submitted concurrently
- **THEN** each committed complaint receives a distinct tracking identifier

### Requirement: Private complaint media
The system MUST store complaint images under generated filenames and serve them only through an authorized request.

#### Scenario: Unauthorized image request
- **WHEN** a user without access to the complaint requests its image
- **THEN** the system returns a forbidden response without exposing the storage path

### Requirement: Complaint ownership scope
The system MUST limit a student to listing and viewing that student's own complaints, with status and category filters available on complaint lists.

#### Scenario: Student requests another student's complaint
- **WHEN** a student requests a complaint owned by another student
- **THEN** the system returns a forbidden response

#### Scenario: Hall leadership views complaints
- **WHEN** Hall Office Staff, Provost, or Super Admin requests complaint records
- **THEN** the system permits access to all complaints subject to selected filters

### Requirement: Complaint assignment
The system SHALL allow Hall Office Staff and Provost to assign a complaint to a house tutor or staff member; Super Admin assignment remains unspecified pending clarification of the conflicting source statements.

#### Scenario: Authorized assignment
- **WHEN** Hall Office Staff or Provost assigns a complaint to an eligible assignee
- **THEN** the assignee and assignment are recorded on the complaint

#### Scenario: Tutor accesses an unassigned complaint
- **WHEN** a tutor requests a complaint not assigned to that tutor
- **THEN** the system returns a forbidden response

### Requirement: Chronological complaint comments
The system SHALL allow the complaint's student and staff roles confirmed through clarification to add comments displayed in chronological order.

#### Scenario: Student and staff exchange comments
- **WHEN** authorized participants add comments at different times
- **THEN** the complaint detail shows each author, time, and comment from oldest to newest

### Requirement: Audited complaint transitions
The system SHALL record every complaint status change with actor, timestamp, and optional note and SHALL reject transitions outside the documented state graph.

#### Scenario: Allowed forward transitions
- **WHEN** an authorized and in-scope actor requests Submitted to Under Review, Under Review to In Progress, In Progress to Resolved, or Resolved to Closed
- **THEN** the system changes status and appends an audit entry

#### Scenario: Allowed rejection transitions
- **WHEN** an authorized and in-scope actor requests Submitted to Rejected or Under Review to Rejected with a reason
- **THEN** the system changes status and appends an audit entry containing the reason

#### Scenario: Invalid transition
- **WHEN** an actor requests any transition outside the documented state graph
- **THEN** the system refuses the transition with a clear validation error and writes no status change

#### Scenario: Resolution without note
- **WHEN** an actor requests In Progress to Resolved without a resolution note
- **THEN** the system refuses the transition with a validation error

### Requirement: Complaint closure
The system SHALL permit Provost and Hall Office Staff to close a Resolved complaint; Super Admin closure remains unspecified pending clarification of the conflicting source statements.

#### Scenario: Documented closer closes a complaint
- **WHEN** Provost or Hall Office Staff requests Resolved to Closed
- **THEN** the system closes the complaint and appends an audit entry

### Requirement: Student complaint reopening
The system SHALL allow the complaint's student to move a Resolved complaint back to In Progress when the request occurs within seven days of resolution.

#### Scenario: Student reopens within the documented period
- **WHEN** the complaint owner requests reopening within seven days of resolution
- **THEN** the system returns the complaint to In Progress and appends an audit entry

#### Scenario: Student reopens outside the documented period
- **WHEN** the complaint owner requests reopening after the documented seven-day period
- **THEN** the system refuses the transition with a clear error

### Requirement: Complaint aging and overdue state
The system SHALL show days open and SHALL flag as overdue any complaint older than seven days that is not Resolved, Closed, or Rejected.

#### Scenario: Unresolved complaint passes seven days
- **WHEN** a complaint remains in Submitted, Under Review, or In Progress for more than seven days
- **THEN** the complaint is visibly marked overdue and shows its days open

### Requirement: Resolution-time measurement
The system SHALL measure complaint resolution time from Submitted to Resolved.

#### Scenario: Complaint becomes resolved
- **WHEN** a complaint transitions from In Progress to Resolved
- **THEN** its resolution duration is calculated from its submission time to that resolution time
