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

### Requirement: House Tutor complaint visibility
The system SHALL limit an active House Tutor to complaints assigned to that tutor. Assigned block membership alone MUST NOT grant complaint visibility.

#### Scenario: Tutor views an assigned complaint
- **WHEN** an active House Tutor lists or opens a complaint assigned to them
- **THEN** the system permits access to that complaint

#### Scenario: Assigned block does not grant visibility
- **WHEN** an active House Tutor requests a complaint in an assigned block that is not assigned to them
- **THEN** the system returns a forbidden response

#### Scenario: Assigned roomless complaint
- **WHEN** an active House Tutor requests a complaint assigned to them that has no room
- **THEN** the system permits access to that complaint

#### Scenario: Inactive tutor requests a complaint
- **WHEN** an inactive House Tutor requests a complaint assigned to them
- **THEN** the system returns a forbidden response

### Requirement: Complaint assignment
The system SHALL allow Hall Office Staff, Provost, and Super Admin to assign a complaint to an active House Tutor or a Hall Office Staff member.

#### Scenario: Authorized assignment
- **WHEN** Hall Office Staff, Provost, or Super Admin assigns a complaint to an eligible assignee
- **THEN** the assignee and assignment are recorded on the complaint

#### Scenario: Ineligible assignee
- **WHEN** an assigner targets a user who is not an active House Tutor or Hall Office Staff
- **THEN** the system refuses the assignment

#### Scenario: Tutor accesses an unassigned complaint
- **WHEN** a tutor requests a complaint not assigned to that tutor
- **THEN** the system returns a forbidden response

### Requirement: Chronological complaint comments
The system SHALL allow the complaint owner, current assignee, Hall Office Staff, Provost, and Super Admin to add comments displayed in chronological order.

#### Scenario: Student and staff exchange comments
- **WHEN** authorized participants add comments at different times
- **THEN** the complaint detail shows each author, time, and comment from oldest to newest

#### Scenario: Unauthorized commenter
- **WHEN** any other user attempts to add a complaint comment
- **THEN** the system returns a forbidden response

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

### Requirement: Staff complaint transition authorization
The system SHALL allow Hall Office Staff, Provost, and Super Admin to move Submitted to Under Review or Rejected, Under Review to In Progress or Rejected, and In Progress to Resolved. Only Provost and Hall Office Staff may move Resolved to Closed.

#### Scenario: Staff rejection
- **WHEN** Hall Office Staff, Provost, or Super Admin requests Submitted to Rejected or Under Review to Rejected with a reason
- **THEN** the system changes status and appends an audit entry containing the reason

#### Scenario: Super Admin attempts closure
- **WHEN** a Super Admin requests Resolved to Closed
- **THEN** the system returns a forbidden response and writes no status change

### Requirement: House Tutor complaint transition authorization
The system SHALL allow an assigned House Tutor to move only Submitted to Under Review, Under Review to In Progress, and In Progress to Resolved. A House Tutor MUST NOT reject, close, or reopen a complaint.

#### Scenario: Assigned tutor performs a forward transition
- **WHEN** an assigned House Tutor requests Submitted to Under Review, Under Review to In Progress, or In Progress to Resolved
- **THEN** the system changes status and appends an audit entry

#### Scenario: Assigned tutor attempts rejection
- **WHEN** an assigned House Tutor requests a transition to Rejected
- **THEN** the system returns a forbidden response and writes no status change

### Requirement: Complaint closure
The system SHALL permit only Provost and Hall Office Staff to close a Resolved complaint. Super Admin MUST NOT close complaints.

#### Scenario: Documented closer closes a complaint
- **WHEN** Provost or Hall Office Staff requests Resolved to Closed
- **THEN** the system closes the complaint and appends an audit entry

#### Scenario: Super Admin closure is refused
- **WHEN** a Super Admin requests Resolved to Closed
- **THEN** the system returns a forbidden response and writes no status change

### Requirement: Student complaint reopening
The system SHALL allow the complaint owner to move a Resolved complaint back to In Progress when the request occurs within a rolling 168-hour window after resolution, inclusive of the exact boundary. The window SHALL be evaluated using UTC timestamps.

#### Scenario: Student reopens at the exact boundary
- **WHEN** the complaint owner requests reopening exactly 168 hours after resolution
- **THEN** the system returns the complaint to In Progress and appends an audit entry

#### Scenario: Student reopens outside the documented period
- **WHEN** the complaint owner requests reopening more than 168 hours after resolution
- **THEN** the system refuses the transition with a clear error

### Requirement: Complaint aging and overdue state
The system SHALL show days open and SHALL flag as overdue any complaint older than seven days that is not Resolved, Closed, or Rejected.

#### Scenario: Unresolved complaint passes seven days
- **WHEN** a complaint remains in Submitted, Under Review, or In Progress for more than seven days
- **THEN** the complaint is visibly marked overdue and shows its days open

### Requirement: Resolution-time measurement
The system SHALL measure complaint resolution time from the complaint's submission time to its latest resolution time. Reopening SHALL clear the current `resolved_at` value; a subsequent resolution SHALL set a new latest `resolved_at` value. Earlier resolution events SHALL remain in the complaint status history.

#### Scenario: Complaint becomes resolved
- **WHEN** a complaint transitions from In Progress to Resolved
- **THEN** its resolution duration is calculated from its submission time to that resolution time

#### Scenario: Reopened complaint is resolved again
- **WHEN** a Resolved complaint is reopened and later resolved again
- **THEN** its resolution duration is calculated from its submission time to the latest resolution time
