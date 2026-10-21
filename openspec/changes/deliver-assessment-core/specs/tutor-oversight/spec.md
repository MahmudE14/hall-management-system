# Spec Delta

## Purpose

Defines house-tutor administration and limits each tutor's student and complaint access to documented block assignments and direct complaint assignments.

## ADDED Requirements

### Requirement: Tutor administration
The system SHALL allow Provost and Super Admin users to create, edit, and deactivate house tutors with name, designation, department, phone, and email.

#### Scenario: Authorized user creates a tutor
- **WHEN** a Provost or Super Admin submits valid tutor details
- **THEN** the system creates an active tutor record

#### Scenario: Unauthorized user manages a tutor
- **WHEN** another role attempts to create, edit, or deactivate a tutor
- **THEN** the system returns a forbidden response

### Requirement: Tutor block assignments
The system SHALL support assigning a tutor to one or more blocks and assigning a block to multiple tutors.

#### Scenario: Tutor is assigned multiple blocks
- **WHEN** an authorized user assigns two blocks to a tutor
- **THEN** both assignments are retained and visible in the tutor record

### Requirement: Tutor student scope
The system MUST limit a tutor's student access to students currently residing in the tutor's assigned blocks.

#### Scenario: Tutor views assigned-block students
- **WHEN** a tutor requests the student list
- **THEN** the system returns only students currently allocated within that tutor's assigned blocks

#### Scenario: Tutor requests an out-of-scope student
- **WHEN** a tutor requests a student outside all assigned blocks
- **THEN** the system returns a forbidden response

### Requirement: Tutor complaint scope
The system MUST allow a tutor to view or update only complaints directly assigned to that tutor.

#### Scenario: Tutor opens an assigned complaint
- **WHEN** a tutor requests a complaint assigned to that tutor
- **THEN** the system provides the complaint and documented update controls

#### Scenario: Tutor opens an unassigned complaint
- **WHEN** a tutor requests a complaint not assigned to that tutor
- **THEN** the system returns a forbidden response
