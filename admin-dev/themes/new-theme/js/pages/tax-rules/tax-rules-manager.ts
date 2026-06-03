/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

import DynamicPaginator from '@components/pagination/dynamic-paginator';
import IframeModal from '@components/modal/iframe-modal';
import PaginatedTaxRulesService from '@pages/tax-rules/service/paginated-tax-rules-service';
import TaxRulesListRenderer from '@pages/tax-rules/tax-rules-list-renderer';

import ClickEvent = JQuery.ClickEvent;

const {$} = window;

const LIST_CONTAINER_ID = '#tax-rules-list-container';
const PAGINATION_CONTAINER_ID = '#tax-rules-pagination';
const MODAL_ID = 'tax-rule-form-modal';

export default class TaxRulesManager {
  private paginator!: DynamicPaginator;

  private listContainer!: HTMLElement;

  constructor() {
    const container = document.querySelector<HTMLElement>(LIST_CONTAINER_ID);

    if (!container) {
      return;
    }

    this.listContainer = container;
    const listUrl = container.dataset.listUrl!;
    const createUrl = container.dataset.createUrl!;

    this.paginator = new DynamicPaginator(
      PAGINATION_CONTAINER_ID,
      new PaginatedTaxRulesService(listUrl),
      new TaxRulesListRenderer(() => this.paginator.paginate(1)),
      1,
    );

    this.initAddButton(createUrl);
    this.initEditButtons();
  }

  private initAddButton(createUrl: string): void {
    const addButton = document.querySelector<HTMLElement>('.js-add-tax-rule-btn');

    if (!addButton) {
      return;
    }

    addButton.addEventListener('click', (e) => {
      e.stopImmediatePropagation();
      this.openModal(
        `${createUrl}${createUrl.includes('?') ? '&' : '?'}liteDisplaying=1`,
        addButton.dataset.modalTitle ?? 'Add new tax rule',
      );
    });
  }

  private initEditButtons(): void {
    // Delegated listener — catches edit buttons added dynamically by the renderer
    $(this.listContainer).on('click', '.js-edit-tax-rule-btn', (event: ClickEvent) => {
      if (!(event.currentTarget instanceof HTMLElement)) {
        return;
      }

      const editButton = event.currentTarget;
      const {editUrl} = editButton.dataset;

      if (!editUrl) {
        return;
      }

      this.openModal(
        `${editUrl}${editUrl.includes('?') ? '&' : '?'}liteDisplaying=1`,
        editButton.dataset.modalTitle ?? 'Edit tax rule',
      );
    });
  }

  private openModal(iframeUrl: string, modalTitle: string): void {
    const iframeModal = new IframeModal({
      id: MODAL_ID,
      iframeUrl,
      closable: true,
      modalTitle,
      autoSize: true,
      autoScrollUp: true,
      closeOnConfirm: true,
      onLoaded: (iframe: HTMLIFrameElement): void => {
        if (!iframe.contentWindow) {
          return;
        }

        const iframeDoc = iframe.contentWindow.document;
        const closeMarker = iframeDoc.querySelector('[data-modal-close]');

        if (closeMarker) {
          iframeModal.hide();
          this.paginator.paginate(1);
          return;
        }

        // Wire cancel buttons inside the iframe to close the modal
        iframeDoc.querySelectorAll<HTMLElement>('.cancel-btn').forEach((btn) => {
          btn.addEventListener('click', (e) => {
            e.preventDefault();
            iframeModal.hide();
          });
        });
      },
    });
    iframeModal.show();
  }
}
