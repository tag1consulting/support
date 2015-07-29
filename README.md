**About This Branch**
This is a complete rewrite and rearchitecture of the Drupal support module
(http://drupal.org/project/support). When functional, the code will be
moved back to Drupal.org and tagged for an 8.x release.

**Architecture**
@todo: add more detail
* Clients: enforced using Organic Groups
* Tickets: custom entity type, supporting optional time tracking and billing.
* Ticket listings: built with Views
* Mail intergration: plan to implement using OG Mailinglist

Support-specific entities and functionality will live in the Support project.
All functionality will be brought together with an install profile.

A best-effort migration path will be made available from the 6.x-1.x Support
module (this is the version we use internally). We hope the community will
contribute a patch to add support for a 7.x-1.x migration.

**Sponsor**
Development on the Support module is sponsored by Tag1 Consulting.
