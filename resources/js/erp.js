import { install as installDialog } from './erp/dialog.js';
import { install as installCheckAll } from './erp/check-all.js';
import { install as installLegacyBridge } from './erp/legacy-bridge.js';
import { toast } from './erp/toast.js';

const dialog = installDialog(document);
installCheckAll(document, window);
installLegacyBridge(dialog, window);

window.erp = Object.assign(window.erp || {}, { dialog, toast });
