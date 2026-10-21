# Spec Delta

## Purpose

Defines who can enter protected hall-management areas, how student access is approved, and which fictional identities must be available for assessment.

## ADDED Requirements

### Requirement: Seeded role-aware authentication
The system SHALL allow the six required fictional demo users to log in and log out, and SHALL authorize protected operations from their assigned roles on the server.

#### Scenario: Required demo identities are available
- **WHEN** the application is migrated and seeded
- **THEN** accounts exist for Super Admin, Provost, Hall Office Staff, North Block House Tutor, Club Admin who is also a Student, and Resident Student using the specified assessment emails and password

#### Scenario: Unauthorized operation is refused
- **WHEN** an authenticated user requests an operation not granted to any assigned role
- **THEN** the system returns a forbidden response rather than relying on hidden interface controls

### Requirement: Student self-registration
The system SHALL accept student registration with full name, 10-digit registration number, department, session, Bangladeshi-format phone, student-domain email, and password.

#### Scenario: Valid student registration
- **WHEN** a visitor submits valid registration details with a unique registration number and unique `@student.du.test` email
- **THEN** the system creates a Pending student account with a hashed password

#### Scenario: Invalid or duplicate registration
- **WHEN** a visitor submits a non-10-digit registration number, invalid phone, non-student-domain email, duplicate registration number, or duplicate email
- **THEN** the system rejects the registration with field-level validation errors

### Requirement: Registration decisions
The system SHALL allow Hall Office Staff, Provost, and Super Admin to approve or reject Pending students, and SHALL require a reason for rejection.

#### Scenario: Pending student is approved
- **WHEN** an authorized registration reviewer approves a Pending student
- **THEN** the student becomes eligible for protected student capabilities

#### Scenario: Pending student is rejected
- **WHEN** an authorized registration reviewer rejects a Pending student with a reason
- **THEN** the system records the rejection and reason and continues to deny protected student capabilities

#### Scenario: Rejection without reason
- **WHEN** an authorized registration reviewer attempts to reject a Pending student without a reason
- **THEN** the system rejects the decision with a validation error

### Requirement: Approval-gated student capabilities
The system MUST permit only approved students to receive a seat, submit a complaint, or request club membership.

#### Scenario: Pending student requests a protected capability
- **WHEN** a Pending student attempts to receive a seat, submit a complaint, or request club membership
- **THEN** the system refuses the request

### Requirement: Multi-role users
The system SHALL support a user holding more than one documented role without creating duplicate identities.

#### Scenario: Club administrator is also a student
- **WHEN** the seeded Club Admin signs in
- **THEN** the account can exercise documented Student behavior and own-club Club Admin behavior

### Requirement: Super Admin user and role management
The system SHALL allow only Super Admin to list system users and assign or remove documented roles.

#### Scenario: Super Admin changes a role assignment
- **WHEN** Super Admin assigns or removes a documented role for a user
- **THEN** the user's role assignments reflect the change without creating another user identity

#### Scenario: Another role manages users or roles
- **WHEN** a user without the Super Admin role attempts to manage users or role assignments
- **THEN** the system returns a forbidden response

### Requirement: Student profile maintenance
The system SHALL allow a Student to maintain that student's own permitted profile details while preserving registration and email validation rules.

#### Scenario: Student updates own profile
- **WHEN** an approved Student submits valid changes to editable profile fields
- **THEN** the system updates that student's profile only

#### Scenario: Student updates another profile
- **WHEN** a Student attempts to update another student's profile
- **THEN** the system returns a forbidden response
