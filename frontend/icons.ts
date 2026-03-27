import { dom, library } from '@fortawesome/fontawesome-svg-core';
import { fab } from '@fortawesome/free-brands-svg-icons';
import { far } from '@fortawesome/free-regular-svg-icons';
import { fas } from '@fortawesome/free-solid-svg-icons';

// Register full packs so newly used aliases in templates keep rendering
// without needing to maintain a manual allowlist.
library.add(fas, far, fab);

export { dom };
