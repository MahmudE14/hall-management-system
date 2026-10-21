# Spec Delta

## Purpose

Defines secure notice authoring and a public, searchable notice board that never exposes drafts and provides controlled PDF attachment downloads.

## ADDED Requirements

### Requirement: Notice authoring
The system SHALL allow authorized roles to create notices with title, body, category, Draft or Published status, pinned flag, and an optional PDF no larger than 5 MB stored under a generated filename.

#### Scenario: Authorized role creates a draft
- **WHEN** an authorized user submits valid notice details with Draft status
- **THEN** the system stores the notice without exposing it publicly

#### Scenario: Invalid notice attachment
- **WHEN** an author uploads a non-PDF, executable file, or PDF larger than 5 MB
- **THEN** the system rejects the upload and stores no file

### Requirement: Category-scoped notice authorization
The system SHALL allow Hall Office Staff, Provost, and Super Admin to publish notices, MUST restrict Club Admin publishing to the Club category, and SHALL enforce all notice permissions on the server.

#### Scenario: Club Admin publishes a club notice
- **WHEN** a Club Admin publishes a valid notice in the Club category
- **THEN** the notice becomes publicly visible

#### Scenario: Club Admin selects an official category
- **WHEN** a Club Admin attempts to create or publish a Provost Office or Hall Office notice
- **THEN** the system returns a forbidden response

### Requirement: Public notice discovery
The system SHALL provide a public paginated list containing only Published notices, ordered with pinned notices first and then newest notices.

#### Scenario: Visitor lists notices
- **WHEN** an unauthenticated visitor opens the notice board
- **THEN** the visitor sees Published notices only, with pinned notices before unpinned notices and newest first within each group

#### Scenario: Visitor searches and filters
- **WHEN** a visitor supplies title search text and a category filter
- **THEN** the system returns only matching Published notices with pagination metadata

### Requirement: Draft notice isolation
The system MUST NOT expose Draft notices through public lists, public detail routes, searches, or attachment routes.

#### Scenario: Visitor requests a draft notice directly
- **WHEN** a visitor requests the identifier of a Draft notice
- **THEN** the system responds as though no public notice exists

### Requirement: Published notice detail and download
The system SHALL provide public details for a Published notice and a working download when that notice has an attachment.

#### Scenario: Visitor downloads a published attachment
- **WHEN** a visitor requests the attachment of a Published notice
- **THEN** the system returns the stored PDF as a download
