# WASE: Web Appointment Scheduling Engine

The WASE system allows people to make themselves available for appointments through the creation of calendars on which availability can be advertised, and allows other people to make appointments with those who are available without going through a mediated exchange. It is primarily intended for use at Universities and Colleges in support of office and advising hours. It can also be used to make various resources (rooms, equipment, facilities) available for appointments/reservations. WASE allows various restrictions to the making of appointments, and it can sync appointments into various calendar systems (Exchange, Google).

WASE was designed to run as a hosting system, with a set of institutions, or a set of schools or offices at an institution, all sharing the same code base, each with its own database (MySQL) and configuration file. So, for example, a single central office could run one instance of WASE which would serve multiple separate campuse, each campus having it's own database and configuration file (no mixing of data across campuses). It can also, of course, be used by a single institution or organization.


Installation instructions can be found in the INSTALL.md file.  An overview of WASE can be found in the public/docs directory as WASE.docx or WASE.pdf.

For further details, contact Serge Goldstein at Princeton University (serge@princeton.edu).