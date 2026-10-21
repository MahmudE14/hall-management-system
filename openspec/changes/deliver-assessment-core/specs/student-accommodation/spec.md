# Spec Delta

## Purpose

Defines the hall's block, room, and seat structure and the controlled workflow that makes an approved student Resident while retaining accommodation history.

## ADDED Requirements

### Requirement: Hall accommodation structure
The system SHALL represent one Hall containing Blocks containing Rooms containing individually identifiable Seats, with each room recording its number, floor, and capacity.

#### Scenario: Staff views room occupancy
- **WHEN** authorized Hall Office Staff opens the accommodation list
- **THEN** each room shows its capacity and occupied and free seat counts

#### Scenario: Staff manages a block or room
- **WHEN** authorized Hall Office Staff submits valid block or room data
- **THEN** the system creates or updates the requested record

### Requirement: Accommodation structure authorization
The system SHALL allow Hall Office Staff and Super Admin to manage blocks, rooms, and seats, while Provost has view-only access to that structure.

#### Scenario: Provost attempts a structure change
- **WHEN** Provost attempts to create or edit a block, room, or seat
- **THEN** the system returns a forbidden response

#### Scenario: Super Admin changes the structure
- **WHEN** Super Admin submits a valid block, room, or seat change
- **THEN** the system persists the change

### Requirement: Room capacity protection
The system MUST NOT allow room occupancy to exceed capacity or allow capacity to be reduced below current occupancy.

#### Scenario: Capacity is reduced below occupancy
- **WHEN** an authorized user attempts to set room capacity below the number of active occupants
- **THEN** the system refuses the change with a clear error

### Requirement: Student residency state
The system SHALL classify an approved student as Resident exactly while the student has an active seat allocation and as Attached otherwise.

#### Scenario: Attached student receives a seat
- **WHEN** an approved Attached student is allocated an available seat
- **THEN** the student becomes Resident and the current room appears on the student profile

#### Scenario: Resident student vacates a seat
- **WHEN** an authorized user records a vacancy date and reason for the active allocation
- **THEN** the allocation is closed, the seat becomes available, and the student becomes Attached

### Requirement: Exclusive active allocation
The system MUST enforce at most one active seat for a student and at most one active occupant for a seat, including concurrent requests.

#### Scenario: Student already has an active seat
- **WHEN** an authorized user attempts to allocate another seat to that student
- **THEN** the system refuses the allocation without changing either seat

#### Scenario: Seat already has an active occupant
- **WHEN** an authorized user attempts to allocate the occupied seat to another student
- **THEN** the system refuses the allocation without changing either student

### Requirement: Approved students only receive seats
The system MUST refuse allocation to a student whose registration is not approved.

#### Scenario: Pending student is selected for allocation
- **WHEN** an authorized user attempts to allocate a seat to a Pending student
- **THEN** the system refuses the allocation

### Requirement: Allocation authorization
The system SHALL allow only Hall Office Staff, Provost, and Super Admin to allocate or vacate seats.

#### Scenario: Unauthorized allocation request
- **WHEN** Student, Club Admin, or House Tutor attempts to allocate or vacate a seat
- **THEN** the system returns a forbidden response

### Requirement: Allocation history is retained
The system SHALL preserve every completed and active allocation and SHALL never delete a historical allocation when a seat is vacated.

#### Scenario: Student profile after vacancy
- **WHEN** an authorized viewer opens the profile of a student whose seat was vacated
- **THEN** the previous room, allocation start, vacancy date, and vacancy reason remain visible in allocation history

### Requirement: Student directory and profile
The system SHALL provide authorized users a paginated student list searchable by name or registration number and filterable by block, department, session, and status.

#### Scenario: Filter students by block and status
- **WHEN** an authorized viewer selects a block and Resident status
- **THEN** the system returns only matching students with pagination metadata

#### Scenario: View a student profile
- **WHEN** an authorized viewer opens a student profile
- **THEN** the system shows personal details, current room when allocated, and allocation history

#### Scenario: Student views records
- **WHEN** a Student or Club Admin requests student records
- **THEN** the system returns only that user's own student record

#### Scenario: Staff views all records
- **WHEN** Hall Office Staff, Provost, or Super Admin requests the student directory
- **THEN** the system permits access to all student records subject to selected filters
