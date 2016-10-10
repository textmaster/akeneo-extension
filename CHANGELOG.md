## v1.1.0: PIM 1.6 compatibility

- Drop PHP 5.5 support.
- Use Akeneo 1.6 batch definition system.
- services definition change to add a more specific `pim_` prefix:
  - `textmaster.api.httpclient_factory`
  - `textmaster.mass_edit_action.create_projects`
  - `textmaster.form.create_projects`
  - `textmaster.saver.project`
  - `textmaster.remover.project`
  - `textmaster.repository.webapi`
  - `textmaster.project.builder`
  - `textmaster.document.updater`
  - `textmaster.locale.finder`
  - `textmaster.repository.project`
