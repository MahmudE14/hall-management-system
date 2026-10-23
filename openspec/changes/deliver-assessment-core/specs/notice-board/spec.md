# Spec Delta

## Purpose

Defines secure notice authoring and a public, searchable notice board that never exposes drafts and provides controlled PDF attachment downloads.

## ADDED Requirements

### Requirement: Notice authoring
The system SHALL allow authorized roles to create notices with title, body, category, Draft or Published status, pinned flag, and an optional PDF no larger than 5 MB stored under a generated filename. Authoring SHALL be create-only; editing, deletion, unpublishing, and later publication are not provided.

#### Scenario: Authorized role creates a draft
- **WHEN** an authorized user submits valid notice details with Draft status
- **THEN** the system stores the notice without exposing it publicly

#### Scenario: Invalid notice attachment
- **WHEN** an author uploads a non-PDF, executable file, or PDF larger than 5 MB
- **THEN** the system rejects the upload and stores no file

### Requirement: Notice categories and publication time
The supported categories SHALL be Provost Office, Hall Office, and Club. Published notices SHALL record their creation time as `published_at`; Draft notices SHALL keep `published_at` null.

#### Scenario: Authorized role publishes a notice
- **WHEN** an authorized user submits valid notice details with Published status
- **THEN** the system stores the notice with `published_at` set and makes it publicly visible

### Requirement: Category-scoped notice authorization
The system SHALL allow Hall Office Staff, Provost, and Super Admin to create notices in any category, MUST restrict Club Admin authoring to the Club category for both Draft and Published notices, and SHALL enforce all notice permissions on the server. Club Admin authoring SHALL require an active account and the Club Admin role, not an approved Student profile.

#### Scenario: Club Admin publishes a club notice
- **WHEN** a Club Admin publishes a valid notice in the Club category
- **THEN** the notice becomes publicly visible

#### Scenario: Club Admin creates a draft club notice
- **WHEN** a Club Admin submits a valid Club notice with Draft status and the pinned flag
- **THEN** the system stores the Draft notice without exposing it publicly

#### Scenario: Club Admin selects an official category
- **WHEN** a Club Admin attempts to create or publish a Provost Office or Hall Office notice
- **THEN** the system returns a forbidden response

### Requirement: Administrative notice listing
The system SHALL provide an authorized administrative list containing both Draft and Published notices. Hall Office Staff, Provost, and Super Admin SHALL see all notices; a Club Admin SHALL see only notices created by that Club Admin.

#### Scenario: Staff list notices
- **WHEN** Hall Office Staff, Provost, or Super Admin opens the administrative notice list
- **THEN** the list includes Draft and Published notices

#### Scenario: Club Admin lists own notices
- **WHEN** a Club Admin opens the administrative notice list
- **THEN** the list includes only Draft and Published notices created by that Club Admin

### Requirement: Public notice discovery
The system SHALL provide a public paginated list at `/notices` containing only Published notices, ordered with pinned notices first, then `published_at` descending, then ID descending. The list SHALL support title search and category filtering.

#### Scenario: Visitor lists notices
- **WHEN** an unauthenticated visitor opens the notice board
- **THEN** the visitor sees Published notices only, with pinned notices before unpinned notices and newest first within each group

#### Scenario: Visitor searches and filters
- **WHEN** a visitor supplies title search text and a category filter
- **THEN** the system returns only matching Published notices with pagination metadata

### Requirement: Draft notice isolation
The system MUST NOT expose Draft notices through public lists, public detail routes, searches, or attachment routes. Public Draft detail and attachment requests SHALL return `404`.

#### Scenario: Visitor requests a draft notice directly
- **WHEN** a visitor requests the identifier of a Draft notice
- **THEN** the system responds with `404` as though no public notice exists

### Requirement: Published notice detail and download
The system SHALL provide public details for a Published notice at `/notices/{notice}` using a guest-safe layout and a working download when that notice has an attachment. Download responses SHALL use safe generated filenames.

#### Scenario: Visitor downloads a published attachment
- **WHEN** a visitor requests the attachment of a Published notice
- **THEN** the system returns the stored PDF as a download with a generated filename
