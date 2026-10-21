# Spec Delta

## Purpose

Defines the club directory and the scoped membership workflow through which approved students request access and club administrators manage only their own clubs.

## ADDED Requirements

### Requirement: Club directory
The system SHALL list clubs with name, short description, optional logo, club administrator, and member count.

#### Scenario: User views the club directory
- **WHEN** a user opens the club directory
- **THEN** the system displays the documented details and current active-member count for each club

### Requirement: Approved-student membership request
The system SHALL allow an approved student to request membership in a club.

#### Scenario: Approved student requests membership
- **WHEN** an approved student with no Pending or Active request for the club submits a request
- **THEN** the system records a Pending membership request

#### Scenario: Unapproved student requests membership
- **WHEN** a Pending or Rejected student submits a membership request
- **THEN** the system refuses the request

### Requirement: Duplicate membership prevention
The system MUST prevent a student from having more than one Pending or Active membership record for the same club, including concurrent requests.

#### Scenario: Duplicate request
- **WHEN** a student with a Pending or Active membership for a club requests that club again
- **THEN** the system refuses the request without creating another membership record

### Requirement: Own-club membership administration
The system SHALL allow a Club Admin to approve or reject Pending requests and remove Active members only for clubs administered by that user, and SHALL allow Super Admin to approve club membership.

#### Scenario: Club Admin approves own-club request
- **WHEN** a Club Admin approves a Pending request for an administered club
- **THEN** the membership becomes Active and the club member count increases

#### Scenario: Club Admin manages another club
- **WHEN** a Club Admin attempts to decide or remove membership for a club not administered by that user
- **THEN** the system returns a forbidden response

#### Scenario: Super Admin approves membership
- **WHEN** Super Admin approves a Pending club request
- **THEN** the membership becomes Active

### Requirement: Own-club page administration
The system SHALL allow a Club Admin to edit only the club page administered by that user.

#### Scenario: Club Admin edits own club
- **WHEN** a Club Admin submits valid changes for an administered club
- **THEN** the club page reflects the changes

#### Scenario: Club Admin edits another club
- **WHEN** a Club Admin submits changes for a club not administered by that user
- **THEN** the system returns a forbidden response

### Requirement: Safe club logo upload
The system MUST validate club logo type and size, reject executable content, and store an accepted logo under a generated filename.

#### Scenario: Unsafe club logo
- **WHEN** a user uploads an executable, unsupported, or oversized club logo
- **THEN** the system rejects the upload and stores no file

### Requirement: Club administrator assignment
The system SHALL allow Super Admin to assign a club administrator to a club.

#### Scenario: Super Admin assigns a club administrator
- **WHEN** Super Admin selects an approved Student and club
- **THEN** that user becomes the administrator for the selected club
