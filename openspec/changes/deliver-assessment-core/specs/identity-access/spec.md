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

### Requirement: Rejected registration reconsideration
The system SHALL allow existing registration-decision roles to return a Rejected student to Pending while preserving the original rejection reason and complete decision history.

#### Scenario: Authorized reviewer reconsiders a rejection
- **WHEN** Hall Office Staff, Provost, or Super Admin moves a Rejected student to Pending
- **THEN** the student becomes Pending and remains unable to access protected student capabilities

#### Scenario: Reconsideration preserves history
- **WHEN** a Rejected student is returned to Pending
- **THEN** the original rejection reason, original decision actor and time, and reconsideration actor and time remain available in decision history

#### Scenario: Student attempts self-resubmission
- **WHEN** a Rejected student attempts to resubmit or return the registration to Pending
- **THEN** the system refuses the request

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
The system SHALL allow only Super Admin to list and search users, assign or update documented roles, and activate or deactivate accounts.

#### Scenario: Super Admin searches users
- **WHEN** Super Admin searches the user list by supported identity fields
- **THEN** the system returns only matching users with their roles and account status

#### Scenario: Super Admin changes a role assignment
- **WHEN** Super Admin assigns, removes, or updates a documented role for a user
- **THEN** the user's role assignments reflect the change without creating another user identity

#### Scenario: Super Admin deactivates an account
- **WHEN** Super Admin deactivates an active account
- **THEN** the account becomes Inactive without deleting its identity or related records

#### Scenario: Super Admin reactivates an account
- **WHEN** Super Admin activates an Inactive account
- **THEN** the account returns to its applicable pre-deactivation access state with existing roles retained

#### Scenario: Another role manages users or roles
- **WHEN** a user without the Super Admin role attempts to manage users or role assignments
- **THEN** the system returns a forbidden response

### Requirement: Inactive account restriction
The system MUST deny Inactive accounts authentication and protected server-side operations.

#### Scenario: Inactive user attempts login
- **WHEN** an Inactive user submits otherwise valid credentials
- **THEN** the system refuses authentication

#### Scenario: Inactive authenticated session requests protected access
- **WHEN** an existing session belongs to an account that has been deactivated
- **THEN** the system refuses the protected request

### Requirement: Restricted Super Admin operations
The system MUST NOT provide Super Admin with administrative user deletion, administrative password reset, or unrestricted user CRUD through this capability.

#### Scenario: Super Admin requests an excluded operation
- **WHEN** Super Admin attempts administrative deletion, password reset, or an unspecified user mutation
- **THEN** the system does not perform the operation

### Requirement: Student profile maintenance
The system SHALL allow a Student to edit only that student's name, email, and phone while preserving email uniqueness, the `@student.du.test` domain, and existing email-verification safeguards.

#### Scenario: Student updates own profile
- **WHEN** an authenticated Student submits valid name, student-domain email, or phone changes
- **THEN** the system updates only those fields on that student's identity and profile

#### Scenario: Student changes email
- **WHEN** an authenticated Student changes to a unique `@student.du.test` email
- **THEN** the system applies the existing email-change verification safeguards before treating the new email as verified

#### Scenario: Student submits invalid email or phone
- **WHEN** a Student submits a duplicate email, non-student-domain email, or invalid Bangladeshi-format phone
- **THEN** the system rejects the update with field-level validation errors

#### Scenario: Student attempts to change read-only identity fields
- **WHEN** a Student attempts to change registration number, department, session, approval state, or residency state
- **THEN** those fields remain unchanged

#### Scenario: Student updates another profile
- **WHEN** a Student attempts to update another student's profile
- **THEN** the system returns a forbidden response
