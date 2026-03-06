// ==========================================
// 1. ICONS LIBRARY
// ==========================================
const createIcon = (svgContent) => (props) => (
    <svg xmlns="http://www.w3.org/2000/svg" width={props.size || 24} height={props.size || 24} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={props.strokeWidth || 2} strokeLinecap="round" strokeLinejoin="round" className={props.className || ''} dangerouslySetInnerHTML={{ __html: svgContent }} />
);

// Navigation & Layout
const LayoutDashboard = createIcon('<rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect>');
const Menu = createIcon('<line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line>');
const X = createIcon('<line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line>');
const LogOut = createIcon('<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line>');
const ArrowRight = createIcon('<line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline>');
const ArrowLeft = createIcon('<line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline>');
const ExternalLink = createIcon('<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line>');
const UploadCloud = createIcon('<polyline points="16 16 12 12 8 16"></polyline><line x1="12" y1="12" x2="12" y2="21"></line><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"></path><polyline points="16 16 12 12 8 16"></polyline>');
const Check = createIcon('<polyline points="20 6 9 17 4 12"></polyline>');
const Copy = createIcon('<rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>');
const Eye = createIcon('<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>');
const EyeOff = createIcon('<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>');
const ChevronRight = createIcon('<polyline points="9 18 15 12 9 6"></polyline>');
const ChevronDown = createIcon('<polyline points="6 9 12 15 18 9"></polyline>');

// Sections
const FileText = createIcon('<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline>');
const ShoppingBag = createIcon('<path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path>');
const Trophy = createIcon('<path d="M8 21h8"></path><path d="M12 17v4"></path><path d="M7 4h10"></path><path d="M18 4h1v6a5 5 0 0 1-5 5h-4a5 5 0 0 1-5-5V4h1"></path>');
const CreditCard = createIcon('<rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line>');
const User = createIcon('<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle>');
const HelpCircle = createIcon('<circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line>');

// Misc
const Clock = createIcon('<circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline>');
const Server = createIcon('<rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect><rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect><line x1="6" y1="6" x2="6.01" y2="6"></line><line x1="6" y1="18" x2="6.01" y2="18"></line>');
const Monitor = createIcon('<rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line>');
const Zap = createIcon('<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>');
const Tv = createIcon('<rect x="2" y="7" width="20" height="15" rx="2" ry="2"></rect><polyline points="17 2 12 7 7 2"></polyline>');
const Star = createIcon('<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>');
const Lock = createIcon('<rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path>');
const Building = createIcon('<line x1="3" y1="21" x2="21" y2="21"></line><line x1="9" y1="8" x2="9" y2="21"></line><line x1="15" y1="8" x2="15" y2="21"></line><polyline points="3 8 12 2 21 8"></polyline><line x1="5" y1="12" x2="19" y2="12"></line>');
const Bitcoin = createIcon('<path d="M11.767 19.089c4.924.868 6.14-6.025 1.216-6.894m-1.216 6.894L5.86 18.047m5.908 1.042l-.347 1.91m1.562-8.846c4.149.731 5.174-5.078 1.025-5.809m-1.025 5.809l-6.122-1.079m6.122 1.079l.346-1.91m-6.469 2.99l.347-1.91m0 0l-1.956-6.095m1.956 6.095l-3.376-.595m7.87-4.184l.347-1.91"></path>');
const Wallet = createIcon('<path d="M20 12V8H6a2 2 0 0 1-2-2c0-1.1.9-2 2-2h12v4"></path><path d="M4 6v12a2 2 0 0 0 2 2h14v-4"></path><path d="M18 12a2 2 0 0 0-2 2c0 1.1.9 2 2 2h4v-4h-4z"></path>');
const AlertTriangle = createIcon('<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line>');

// Sports
const IconFootball = createIcon('<circle cx="12" cy="12" r="10"></circle><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"></path><path d="M2 12h20"></path>');
const IconBasketball = createIcon('<circle cx="12" cy="12" r="10"></circle><path d="M5.65 17.65a8.5 8.5 0 1 1 12.7 0"></path><path d="M12 22V2"></path><path d="M2 12h20"></path>');
const IconFighting = createIcon('<path d="M9 7L3 17l4 4 10-10V7h-4z"></path><path d="M12 12l9 9"></path>');
const IconF1 = createIcon('<path d="M4 17h6l2-6H6l-2 6z"></path><path d="M14 17h6"></path><path d="M20 11h-8"></path>');
const IconNFL = createIcon('<path d="M7 2h10l5 10-10 10L2 12 7 2z"></path><line x1="12" y1="2" x2="12" y2="22"></line><line x1="2" y1="12" x2="22" y2="12"></line>');
const IconTennis = createIcon('<circle cx="12" cy="12" r="10"></circle><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"></path>');
const IconCricket = createIcon('<circle cx="12" cy="12" r="10"></circle><path d="M12 22a10 10 0 0 0 0-20"></path>');

const { useState, useEffect, useMemo, useRef } = React;
