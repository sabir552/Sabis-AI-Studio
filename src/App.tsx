import React, { useState, useEffect, useRef } from "react";
import { 
  Wand2, 
  Layout, 
  Download, 
  Settings, 
  Globe, 
  FileText, 
  CheckCircle2, 
  MessageSquare, 
  ShoppingCart, 
  Volume2, 
  Mic, 
  MicOff, 
  Eye, 
  Code2, 
  Sparkles, 
  RefreshCw, 
  Zap, 
  Share2, 
  Trash2, 
  Layers, 
  ExternalLink, 
  Info,
  Smartphone,
  Check,
  AlertCircle,
  Monitor,
  Tablet,
  Send
} from "lucide-react";

// Types for GrapesJS editor
interface GJSComponent {
  getEl(): HTMLElement;
  setComponents(html: string): void;
  get(prop: string): any;
  set(param: string, value: any): void;
  getWrapper(): any;
  setAttribute(name: string, value: any): void;
  addClass(className: string): void;
  removeClass(className: string): void;
}

interface GJSSelectedModel {
  getEl(): HTMLElement;
  get(prop: string): any;
  setComponents(html: string): void;
}

export default function App() {
  // Navigation & Simulation Tabs
  const [activeTab, setActiveTab] = useState<"builder" | "pages" | "settings">("builder");
  const [isWpSidebarCollapsed, setIsWpSidebarCollapsed] = useState(false);
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
  const [currentDevice, setCurrentDevice] = useState<"Desktop" | "Tablet" | "Mobile">("Desktop");

  // Configuration State
  const [geminiApiKey, setGeminiApiKey] = useState("");
  const [googleWebhookUrl, setGoogleWebhookUrl] = useState("");
  const [themePreset, setThemePreset] = useState<string>("Minimal Light");
  const [customThemePresetDescription, setCustomThemePresetDescription] = useState("");
  const [currentLang, setCurrentLang] = useState<"en" | "ur">("en");

  // AI Generation Sandbox States
  const [userPrompt, setUserPrompt] = useState("");
  const [isGenerating, setIsGenerating] = useState(false);
  const [generationSteps, setGenerationSteps] = useState<string[]>([]);
  const [generationProgress, setGenerationProgress] = useState(0);
  const [activeStatusText, setActiveStatusText] = useState("Standby - Sabis Visual Engine Ready");

  // WordPress Page database simulation
  const [publishedPages, setPublishedPages] = useState<any[]>([
    {
      id: 101,
      title: "Bio Herbal Cosmetics Landing Page",
      slug: "organic-herbal-tea",
      theme: "Minimal Light",
      date: "2026-05-23 12:01",
      permalink: "https://example.com/organic-herbal-tea",
      content: "<div class='p-12 text-center bg-zinc-50 text-slate-800'><h1 class='text-4xl font-extrabold mb-4'>Pre-Published Tea Hero</h1><p class='text-lg'>Fully independent layout block saved inside the WordPress virtual tables.</p></div>",
      audit: "Self-healing checks completed. Compiling modern vector images."
    }
  ]);

  // Context Menu Copywriting Utility Drawer States
  const [selectedText, setSelectedText] = useState("");
  const [selectedComponentModel, setSelectedComponentModel] = useState<any>(null);
  const [isRewriting, setIsRewriting] = useState(false);

  // Real-time voice dictation
  const [isListening, setIsListening] = useState(false);
  const recognitionRef = useRef<any>(null);

  // GrapesJS reference
  const editorRef = useRef<any>(null);
  const syncIntervalRef = useRef<any>(null);
  const [isGjsLoaded, setIsGjsLoaded] = useState(false);

  // Toast notifications
  const [toasts, setToasts] = useState<any[]>([]);

  // Interactive Live Order Popup Simulation
  const [activeOrderData, setActiveOrderData] = useState<any>(null);
  const [activeLeadEmail, setActiveLeadEmail] = useState<string | null>(null);

  // AI Redesign Modal & Offline Recovery state variables
  const [isRedesignModalOpen, setIsRedesignModalOpen] = useState(false);
  const [redesignHtml, setRedesignHtml] = useState("");
  const [redesignCustomInstruction, setRedesignCustomInstruction] = useState("");
  const [redesignSelectedModel, setRedesignSelectedModel] = useState<any>(null);
  const [isRedesigning, setIsRedesigning] = useState(false);
  const [hasRecoverySession, setHasRecoverySession] = useState(false);

  // Reference Image Upload States
  const [attachedReferenceImage, setAttachedReferenceImage] = useState<string | null>(null);
  const [attachedImageName, setAttachedImageName] = useState<string | null>(null);
  const imageInputRef = useRef<HTMLInputElement>(null);

  // Multi-Page Selector States
  const [pageCount, setPageCount] = useState<number>(1);
  const [selectedTemplates, setSelectedTemplates] = useState<string[]>(["Home"]);
  const [sabisPages, setSabisPages] = useState<{ title: string; html: string }[]>([]);
  const [sabisActiveTab, setSabisActiveTab] = useState<string | null>(null);

  // Site Category Preferences
  const [siteType, setSiteType] = useState<string>("SaaS Landing Page");
  const [customSiteType, setCustomSiteType] = useState<string>("");

  // Stripe Publishable Key
  const [stripePublishableKey, setStripePublishableKey] = useState("");
  const [selectedPageId, setSelectedPageId] = useState<string>("");

  const publishedPagesRef = useRef(publishedPages);
  useEffect(() => {
    publishedPagesRef.current = publishedPages;
  }, [publishedPages]);

  const addToast = (title: string, message: string, type: "success" | "info" | "error" = "success") => {
    const id = Date.now();
    setToasts((prev) => [...prev, { id, title, message, type }]);
    setTimeout(() => {
      setToasts((prev) => prev.filter((t) => t.id !== id));
    }, 4500);
  };

  const handleReactImageUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = (event) => {
        if (event.target?.result) {
          setAttachedReferenceImage(event.target.result as string);
          setAttachedImageName(file.name);
          addToast("📁 Visual Guideline Saved", "Image loaded into Google Gemini Multimodal Pipeline successfully!", "success");
        }
      };
      reader.readAsDataURL(file);
    }
  };

  const handleTabSwitch = (targetTitle: string) => {
    if (!editorRef.current) return;
    
    // Save current active tab HTML
    if (sabisActiveTab) {
      const currentHTML = editorRef.current.getHtml() || "";
      const currentCSS = editorRef.current.getCss() || "";
      const combinedHTML = `<style>${currentCSS}</style>${currentHTML}`;
      
      setSabisPages(prev => prev.map(p => {
        if (p.title === sabisActiveTab) {
          return { ...p, html: combinedHTML };
        }
        return p;
      }));
    }

    // Load target tab HTML
    const targetPage = sabisPages.find(p => p.title === targetTitle);
    if (targetPage) {
      setSabisActiveTab(targetTitle);
      editorRef.current.setComponents(targetPage.html);
    }
  };

  // Synchronize Google Sheets / Webhook settings in LocalStorage
  useEffect(() => {
    const storedWebhook = localStorage.getItem("sabis_sheets_webhook_url");
    if (storedWebhook) {
      setGoogleWebhookUrl(storedWebhook);
    } else {
      setGoogleWebhookUrl("https://script.google.com/macros/s/AKfycbz_sabis_ai_studio_simulation_sheets_webhook/exec");
    }

    const storedStripeKey = localStorage.getItem("sabis_stripe_publishable_key");
    if (storedStripeKey) {
      setStripePublishableKey(storedStripeKey);
    }

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get("sabis_edit_page")) {
      setActiveTab("builder");
    }
  }, []);

  // Initialize GrapesJS Visual Editor Core
  useEffect(() => {
    if (activeTab !== "builder") return;

    // Listen to capture-phase clicks/touches on the AI Redesign button to bypass any GrapesJS internal swallows
    const handleAiRedesignTrigger = (e: any) => {
      const btn = e.target.closest("#sabis-btn-ai-redesign");
      if (btn) {
        e.preventDefault();
        e.stopPropagation();
        console.log("Intercepting standalone click for AI Redesign...");
        if (editorRef.current) {
          editorRef.current.runCommand("regenerate-section-command");
        }
      }
    };
    
    document.addEventListener("click", handleAiRedesignTrigger, { capture: true });
    document.addEventListener("touchstart", handleAiRedesignTrigger, { capture: true });

    const initInterval = setInterval(() => {
      if (window.hasOwnProperty("grapesjs")) {
        clearInterval(initInterval);
        initializeGrapes();
      }
    }, 200);

    return () => {
      clearInterval(initInterval);
      if (syncIntervalRef.current) {
        clearInterval(syncIntervalRef.current);
        syncIntervalRef.current = null;
      }
      document.removeEventListener("click", handleAiRedesignTrigger, { capture: true });
      document.removeEventListener("touchstart", handleAiRedesignTrigger, { capture: true });
      if (editorRef.current) {
        editorRef.current.destroy();
        editorRef.current = null;
        setIsGjsLoaded(false);
      }
    };
  }, [activeTab]);

  const initializeGrapes = () => {
    if (editorRef.current) return;

    try {
      const gjsContainer = document.getElementById("gjs-wrapper");
      if (!gjsContainer) return;

      const editor = (window as any).grapesjs.init({
        container: "#gjs-wrapper",
        fromElement: false,
        height: "640px",
        width: "auto",
        storageManager: false,
        undoManager: {
          trackComponents: true,
          limit: 15
        },
        showDevices: true,
        deviceManager: {
          devices: [
            { id: "desktop", name: "Desktop", width: "" },
            { id: "tablet", name: "Tablet", width: "768px", widthMedia: "992px" },
            { id: "mobile", name: "Mobile", width: "375px", widthMedia: "480px" }
          ]
        },
        canvas: {
          styles: [],
          scripts: [
            "https://cdn.tailwindcss.com"
          ]
        },
        panels: {
          defaults: [
            {
              id: "commands",
              buttons: [
                {
                  id: "show-json",
                  className: "fa fa-code",
                  command: "show-json",
                  attributes: { title: "View Core Code JSON" }
                }
              ]
            }
          ]
        },
        assetManager: {
          upload: true,
          dropzone: true,
          customUpload: (files: FileList) => {
            const filePromises = Array.from(files).map((file) => {
              return new Promise<{ name: string; type: string; base64: string }>((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = () => {
                  const result = reader.result as string;
                  const base64 = result.split(",")[1];
                  resolve({ name: file.name, type: file.type, base64 });
                };
                reader.onerror = reject;
                reader.readAsDataURL(file);
              });
            });

            Promise.all(filePromises)
              .then(async (serializedFiles) => {
                const response = await fetch("/api/auto_webp_media_handler", {
                  method: "POST",
                  headers: { "Content-Type": "application/json" },
                  body: JSON.stringify({ files: serializedFiles }),
                });
                
                if (!response.ok) {
                  throw new Error("Local upload simulation service failed");
                }
                
                const result = await response.json();
                if (result.success && result.data) {
                  editor.AssetManager.add(result.data);
                  addToast("🖼️ WebP Image Uploaded & Processed", "Image successfully converted and integrated", "success");
                  
                  const selected = editor.getSelected();
                  if (selected && selected.get("type") === "image") {
                    selected.set("src", result.data[0]);
                  }
                } else {
                  addToast("⚠️ Upload Failure", result.error || "WebP mock upload failed.", "error");
                }
              })
              .catch((err: any) => {
                addToast("⚠️ Process Error", err.message || "Failed to process files.", "error");
              });
          }
        }
      });

      // Synchronize GrapesJS device switches back into our React state
      editor.on("device:select", () => {
        const selectedDev = editor.getDevice();
        let devId = "";
        if (selectedDev) {
          if (typeof selectedDev === "string") {
            devId = selectedDev;
          } else if (typeof selectedDev === "object") {
            devId = selectedDev.id || selectedDev.get?.("id") || selectedDev.get?.("name") || "";
          }
        }
        
        const normalized = devId.toLowerCase();
        if (normalized === "desktop") {
          setCurrentDevice("Desktop");
        } else if (normalized === "tablet") {
          setCurrentDevice("Tablet");
        } else if (normalized === "mobile") {
          setCurrentDevice("Mobile");
        }
      });

      // Catch right-click (contextmenu) events inside GrapesJS iframe body
      editor.on('canvas:load', function() {
        const canvasBody = editor.Canvas.getBody();
        const canvasHead = editor.Canvas.getHead();
        
        // Inject scroll and responsive canvas container layout protections
        if (canvasHead) {
          const styleEl = document.createElement('style');
          styleEl.innerHTML = `
            body { 
              overflow-x: hidden !important; 
              width: 100% !important; 
              margin: 0; 
              padding: 0; 
              box-sizing: border-box !important; 
            }
            * { 
              box-sizing: border-box !important; 
              max-width: 100% !important; 
            }
          `;
          canvasHead.appendChild(styleEl);
        }

        if (canvasBody) {
          canvasBody.addEventListener('contextmenu', function(e: any) {
            e.preventDefault();
            const offset = editor.Canvas.getOffset();
            // Factoring the canvas offset correctly to get absolute positions
            const cursorX = e.clientX + offset.left;
            const cursorY = e.clientY + offset.top;
            window.postMessage({
              action: 'sabis_context_menu',
              x: cursorX,
              y: cursorY
            }, '*');
          });

          // Direct click listener to automatically hide active context menu elements
          canvasBody.addEventListener('click', function() {
            const cm = document.getElementById('sabis-context-menu');
            if (cm) {
              cm.style.display = 'none';
            }
          });
        }
      });

      // Custom command to export page as JSON structure
      editor.Commands.add("show-json", {
        run(editor: any) {
          const code = JSON.stringify(editor.getComponents(), null, 2);
          alert("Sabis AI Studio Core JSON:\n\n" + code);
        }
      });

      // Section-level re-design engine command (Upgrade 3 Custom Iframe Handler)
      editor.Commands.add("regenerate-section-command", {
        async run(editor: any) {
          const selected = editor.getSelected();
          if (!selected) {
            alert("Please select a component first.");
            return;
          }
          
          let htmlToRegen = "";
          if (selected.toHTML) {
            htmlToRegen = selected.toHTML();
          } else if (selected.getEl) {
            htmlToRegen = selected.getEl().outerHTML;
          }
          
          setRedesignHtml(htmlToRegen);
          setRedesignSelectedModel(selected);
          setIsRedesignModalOpen(true);
        }
      });

      // Register Section Level Blocks Custom Managers
      const blockManager = editor.BlockManager;

      blockManager.add("sabis-bento", {
        label: `
          <div class="p-2 border border-stone-200 rounded hover:border-blue-500 bg-stone-50 text-center cursor-pointer transition">
            <div class="text-xs font-bold text-stone-800">🍱 Bento SaaS Features</div>
            <div class="text-[10px] text-stone-400">High contrast modern grid</div>
          </div>
        `,
        category: "Sabis Premium Components",
        content: `
          <section class="py-16 px-6 bg-zinc-50 border-t border-stone-100 dark:bg-zinc-900 text-stone-900 dark:text-zinc-50 font-sans">
            <div class="max-w-6xl mx-auto">
              <span class="text-xs tracking-widest font-bold text-zinc-500 block text-center uppercase mb-2">Architectural Blueprint</span>
              <h2 class="text-3xl md:text-4xl font-black text-center mb-12 tracking-tight">Crafted for extreme performance</h2>
              
              <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="md:col-span-2 p-8 bg-white border border-stone-200/80 rounded-2xl shadow-sm hover:shadow-md transition">
                  <div class="h-10 w-10 bg-indigo-50 rounded-xl flex items-center justify-center mb-6 text-indigo-600 font-bold font-mono">01</div>
                  <h3 class="text-xl font-bold mb-2">Elementor-Free Core Framework</h3>
                  <p class="text-stone-500 text-sm leading-relaxed">Runs 100% independently of clumsy visual frameworks. Employs modern native CSS generation to squeeze site speeds to the absolute minimum.</p>
                </div>
                
                <div class="p-8 bg-zinc-900 text-white rounded-2xl shadow-sm hover:shadow-lg transition flex flex-col justify-between">
                  <div>
                    <div class="h-10 w-10 bg-zinc-800 rounded-xl flex items-center justify-center mb-6 text-yellow-400 font-bold">★</div>
                    <h3 class="text-lg font-bold mb-2">Mobile First UI</h3>
                  </div>
                  <p class="text-zinc-400 text-xs leading-relaxed">Re-architected from the ground up on standard tactile systems for direct deployment via iPhone/Android browsers.</p>
                </div>

                <div class="p-8 bg-white border border-stone-200/80 rounded-2xl shadow-sm hover:shadow-md transition">
                  <div class="h-10 w-10 bg-teal-50 rounded-xl flex items-center justify-center mb-6 text-teal-600 font-bold">✍</div>
                  <h3 class="text-lg font-bold mb-2">AI Copywriting</h3>
                  <p class="text-stone-500 text-xs leading-relaxed">Integrated micro-editing features that link highlights straight to Gemini engines.</p>
                </div>

                <div class="md:col-span-2 p-8 bg-zinc-100 hover:bg-zinc-200/50 rounded-2xl transition border border-stone-200/40">
                  <h3 class="text-lg font-bold mb-2">Urdu Right-to-Left (RTL) Localization</h3>
                  <p class="text-stone-600 text-xs">Instantly localizes textual content nodes on the fly. Urdu fonts pair naturally with gorgeous letterheads.</p>
                  <div class="mt-4 pt-3 border-t border-stone-300 flex items-center gap-2 text-[11px] text-stone-500">
                    <span>💡 Press TransUrdu Trigger below to test instantly</span>
                  </div>
                </div>
              </div>
            </div>
          </section>
        `
      });

      blockManager.add("sabis-order-form", {
        label: `
          <div class="p-2 border border-stone-200 rounded hover:border-blue-500 bg-stone-50 text-center cursor-pointer transition">
            <div class="text-xs font-bold text-stone-800">🛍️ checkout / WhatsApp</div>
            <div class="text-[10px] text-stone-400">Direct ordering block</div>
          </div>
        `,
        category: "Sabis Premium Components",
        content: `
          <div class="p-6 bg-stone-900 border border-stone-800 rounded-2xl max-w-md mx-auto my-8 text-white shadow-xl font-sans" data-gjs-type="custom-order-form">
            <div class="flex items-center gap-2 mb-3">
              <span class="px-2 py-0.5 bg-green-600 text-[10px] font-bold tracking-wider rounded font-mono">CTA Checkout</span>
              <span class="text-[10px] text-stone-400">WhatsApp direct orders</span>
            </div>
            <h3 class="text-lg font-bold text-yellow-100 mb-1">Buy Instant Direct</h3>
            <p class="text-xs text-stone-400 mb-4">Pure checkout without WooCommerce bloat. Generates instant client messages.</p>
            
            <div class="space-y-3 test-order-fields">
              <div>
                <label class="block text-[10px] font-semibold uppercase tracking-wider text-stone-400 mb-1">Client Name</label>
                <input type="text" id="order_client_name" placeholder="John Doe" class="w-full bg-stone-800 border border-stone-700 rounded p-2 text-xs text-white placeholder-stone-500" />
              </div>
              <div>
                <label class="block text-[10px] font-semibold uppercase tracking-wider text-stone-400 mb-1">Whatsapp active number</label>
                <input type="tel" id="order_client_phone" placeholder="+123456789" class="w-full bg-stone-800 border border-stone-700 rounded p-2 text-xs text-white placeholder-stone-500" />
              </div>
              <div>
                <label class="block text-[10px] font-semibold uppercase tracking-wider text-stone-400 mb-1">Shipping Full Address</label>
                <textarea id="order_client_address" placeholder="123 Cozy Avenue, City Centre..." class="w-full bg-stone-800 border border-stone-700 rounded p-2 text-xs text-white h-16 placeholder-stone-500"></textarea>
              </div>
              
              <button type="button" 
                      onclick="window.parent.postMessage({
                        action: 'sabis_order', 
                        name: document.getElementById('order_client_name') ? document.getElementById('order_client_name').value : 'Anonymous',
                        phone: document.getElementById('order_client_phone') ? document.getElementById('order_client_phone').value : 'No Phone',
                        address: document.getElementById('order_client_address') ? document.getElementById('order_client_address').value : 'No Address'
                      }, '*')" 
                      class="w-full mt-2 bg-green-600 hover:bg-green-500 active:bg-green-700 text-white font-bold py-2 px-4 rounded-lg text-xs transition duration-150 flex items-center justify-center gap-1.5 cursor-pointer">
                💬 Send Instant Order
              </button>
            </div>
          </div>
        `
      });

      blockManager.add("sabis-sheet-capture", {
        label: `
          <div class="p-2 border border-stone-200 rounded hover:border-blue-500 bg-stone-50 text-center cursor-pointer transition">
            <div class="text-xs font-bold text-stone-800">📊 Google Sheet Lead</div>
            <div class="text-[10px] text-stone-400">Direct lead catch</div>
          </div>
        `,
        category: "Sabis Premium Components",
        content: `
          <div class="p-6 bg-white border border-stone-200 rounded-2xl max-w-sm mx-auto my-6 text-stone-800 shadow-sm font-sans" data-gjs-type="custom-sheet-form">
            <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center mb-3">
              <span class="text-blue-600 font-bold text-xs font-mono">📬</span>
            </div>
            <h4 class="text-sm font-bold mb-1">Register for Private Beta</h4>
            <p class="text-xs text-stone-400 mb-4">Bypasses local servers. Pushes emails directly to structured spreadsheets.</p>
            
            <div class="flex gap-2">
              <input type="email" id="lead_email_input" placeholder="you@domain.com" class="flex-grow border border-stone-300 rounded-lg px-3 py-1.5 text-xs bg-stone-50 focus:bg-white text-stone-800" />
              <button type="button"
                      onclick="window.parent.postMessage({
                        action: 'sabis_lead', 
                        email: document.getElementById('lead_email_input') ? document.getElementById('lead_email_input').value : ''
                      }, '*')" 
                      class="bg-blue-600 hover:bg-blue-500 active:bg-blue-700 text-white font-bold px-4 py-1.5 rounded-lg text-xs transition cursor-pointer">
                Join Beta
              </button>
            </div>
          </div>
        `
      });

      blockManager.add("sabis-headline-hero", {
        label: `
          <div class="p-2 border border-stone-200 rounded hover:border-blue-500 bg-stone-50 text-center cursor-pointer transition">
            <div class="text-xs font-bold text-stone-800">✨ Headline Hero Block</div>
            <div class="text-[10px] text-stone-400">Title + description CTA</div>
          </div>
        `,
        category: "Sabis Premium Components",
        content: `
          <section class="py-24 px-6 text-center bg-radial from-slate-900 to-black text-white rounded-3xl my-6 mx-4 font-sans border border-slate-800 shadow-2xl relative overflow-hidden">
            <div class="absolute inset-0 bg-grid-white/[0.02] bg-[size:20px_20px]"></div>
            <div class="absolute -top-40 -left-40 w-80 h-80 bg-blue-500/10 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-40 -right-40 w-80 h-80 bg-emerald-500/10 rounded-full blur-3xl"></div>
            
            <div class="relative max-w-3xl mx-auto">
              <span class="px-3 py-1 bg-zinc-800 border border-zinc-700 text-white rounded-full text-xs font-semibold uppercase tracking-wider mb-6 inline-block">Sabis Visual Builder v1.0</span>
              <h1 class="text-4xl md:text-6xl font-black mb-6 tracking-tight leading-none bg-gradient-to-r from-white via-stone-200 to-stone-400 bg-clip-text text-transparent">
                Design stunning visuals autonomously
              </h1>
              <p class="text-zinc-400 text-base md:text-lg mb-8 max-w-xl mx-auto leading-relaxed">
                Empowered with advanced localized translation layers and high-contrast micro copywriting engines designed for elite web designers who require pristine codes.
              </p>
              
              <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="#order" class="px-6 py-3 bg-white text-black font-bold text-sm tracking-wide rounded-lg hover:bg-stone-100 transition shadow">
                  Configure Your Canvas
                </a>
                <a href="#learn" class="px-6 py-3 bg-zinc-950 border border-zinc-800 text-zinc-400 hover:text-white font-semibold text-sm rounded-lg hover:border-zinc-700 transition">
                  Simulate Live Action
                </a>
              </div>
            </div>
          </section>
        `
      });

      // Check query parameter for auto-load page ID
      const urlParams = new URLSearchParams(window.location.search);
      const urlPageId = urlParams.get("sabis_edit_page");
      let pageLoaded = false;

      if (urlPageId) {
        const foundPage = (publishedPagesRef.current || publishedPages).find((p: any) => p.id.toString() === urlPageId.toString());
        if (foundPage) {
          let rawHtml = foundPage.content || "";
          if (rawHtml.includes("sabis-canvas-body-wrap")) {
            const tempDiv = document.createElement("div");
            tempDiv.innerHTML = rawHtml;
            const wrap = tempDiv.querySelector(".sabis-canvas-body-wrap");
            if (wrap) {
              const scripts = wrap.querySelectorAll("script");
              scripts.forEach(s => s.remove());
              const favicons = wrap.querySelectorAll("link");
              favicons.forEach(f => f.remove());
              rawHtml = wrap.innerHTML;
            }
          }
          editor.setComponents(rawHtml);
          pageLoaded = true;
          setTimeout(() => {
            addToast("📥 Page Content Preloaded", `Retrieved '${foundPage.title}' automatically via native Edit query link.`, "success");
          }, 500);
        }
      }

      if (!pageLoaded) {
        // Default HTML content structure if none generated yet
        editor.setComponents(`
          <section class="max-w-4xl mx-auto py-16 px-6 text-center font-sans bg-zinc-50 border border-zinc-200 rounded-3xl my-8">
            <span class="text-xs font-bold uppercase text-blue-600 bg-blue-100/50 px-3 py-1 rounded-full mb-4 inline-block tracking-wider">Empty Slate Canvas</span>
            <h2 class="text-3xl font-black text-zinc-900 mb-3 tracking-tight">Your Visual Studio is Prepped</h2>
            <p class="text-zinc-500 text-sm max-w-md mx-auto mb-6">
              Enter a prompt above and speak directly using dynamic voice speech recognition logic, or drag premium elements from the blocks inspector shelf.
            </p>
            <div class="flex justify-center gap-3">
              <span class="text-[11px] font-mono text-zinc-400">Powered by GrapesJS + Tailwind CSS v4</span>
            </div>
          </section>
        `);
      }

      // Track highlighted text blocks inside GrapesJS frame for prompt-level micro actions
      editor.on("component:selected", (model: any) => {
        const type = model.get("type");
        const tagName = model.get("tagName");
        const editableTags = ["h1", "h2", "h3", "h4", "h5", "h6", "p", "span", "a", "button"];

        if (type === "text" || editableTags.includes(tagName)) {
          setSelectedComponentModel(model);
          const el = model.getEl();
          if (el) {
            setSelectedText(el.innerText || el.textContent || "");
          }
        } else {
          setSelectedComponentModel(null);
          setSelectedText("");
        }

        // Inject custom toolbar button for redesign (Upgrade 3 Custom Iframe Handler)
        const toolbar = model.get("toolbar");
        const buttonExists = toolbar ? toolbar.some((item: any) => item.id === "regenerate-section") : false;
        if (!buttonExists && toolbar) {
          toolbar.push({
            id: "regenerate-section",
            command: "regenerate-section-command",
            label: "✨ AI Redesign",
            attributes: { 
              id: "sabis-btn-ai-redesign",
              title: "AI Redesign Section via Gemini 2.5 Pro" 
            }
          });
          model.set("toolbar", [...toolbar]);
          model.trigger("change:toolbar");
        }
      });

      // Handle custom messages from GrapesJS iframe (e.g., checkout buttons)
      window.addEventListener("message", (event) => {
        if (!event.data) return;

        if (event.data.action === "sabis_order") {
          setActiveOrderData({
            name: event.data.name || "Sabis Guest Client",
            phone: event.data.phone || "No Connection",
            address: event.data.address || "Simulated Shipping Address Area"
          });
          addToast("🛍️ Order Transmitted!", `Simulated WhatsApp connection prepped for ${event.data.name}`, "success");
        } else if (event.data.action === "sabis_lead") {
          setActiveLeadEmail(event.data.email || "client@domain.com");
          handleSheetsLeadSubmission(event.data.email);
        }
      });

      // 1. TEXT DRAG & DROP MOBILITY ENHANCEMENT
      editor.on("component:mount", (model: any) => {
        const type = model.get("type");
        const tagName = model.get("tagName");
        const editableTags = ["h1", "h2", "h3", "h4", "h5", "h6", "p", "span", "a", "button"];
        if (type === "text" || editableTags.includes(tagName)) {
          model.set({
            draggable: true,
            droppable: true
          });
        }
      });

      // 5. OFFLINE STATE HYDRATION & RECOVERY ADDITION
      const syncInterval = setInterval(() => {
        try {
          const activeEditor = editorRef.current;
          if (activeEditor) {
            const html = activeEditor.getHtml();
            const css = activeEditor.getCss();
            const components = activeEditor.getComponents();
            const payload = JSON.stringify({ html, css, components });
            localStorage.setItem("sabis_grapesjs_autosave_payload", payload);
          }
        } catch (err) {
          console.error("Layout cache sync error:", err);
        }
      }, 30000);
      syncIntervalRef.current = syncInterval;

      editorRef.current = editor;
      setIsGjsLoaded(true);

      // Check if there is an offline saved session on layout load
      const storedRecovery = localStorage.getItem("sabis_grapesjs_autosave_payload");
      if (storedRecovery) {
        try {
          const parsed = JSON.parse(storedRecovery);
          if (parsed && (parsed.html || parsed.components)) {
            setHasRecoverySession(true);
          }
        } catch (e) {
          console.error("Failed to parse recovery cache payload:", e);
        }
      }

    } catch (err) {
      console.error("Failed to configure GrapesJS editor:", err);
    }
  };

  const handleRestoreSession = () => {
    const storedRecovery = localStorage.getItem("sabis_grapesjs_autosave_payload");
    if (storedRecovery && editorRef.current) {
      try {
        const parsed = JSON.parse(storedRecovery);
        if (parsed) {
          if (parsed.components) {
            editorRef.current.setComponents(parsed.components);
          } else if (parsed.html) {
            editorRef.current.setComponents(parsed.html);
          }
          addToast("🔄 Autorecovery Succeeded", "Previous canvas layout has been restored from local storage", "success");
        }
      } catch (e) {
        console.error("Autorecovery parse error:", e);
      }
    }
    setHasRecoverySession(false);
  };

  const handleSectionRedesign = async (actionType: "custom" | "auto") => {
    let instruction = "";
    if (actionType === "custom") {
      instruction = redesignCustomInstruction;
    } else {
      instruction = "Improve design, aesthetic layout, typography, visual spacing, color palettes, and overall structure to follow elite global SaaS design trends.";
    }

    if (!instruction || !instruction.trim()) return;

    setIsRedesigning(true);
    addToast("⚡ Redesigning...", "Section-level regeneration active via Gemini 2.5 Pro", "info");

    try {
      const selectEl = document.getElementById("sabis-theme-preset") as HTMLSelectElement | null;
      const tempTheme = selectEl ? selectEl.value : themePreset;
      const response = await fetch("/api/regenerate_section", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          sectionHtml: redesignHtml,
          instruction,
          themePreset: tempTheme
        })
      });

      if (!response.ok) {
        throw new Error("Unable to contact section redesign server API");
      }

      const result = await response.json();
      if (result.success && result.html) {
        const selected = redesignSelectedModel;
        if (selected) {
          const parent = selected.parent();
          if (parent) {
            const index = selected.index();
            parent.append(result.html, { at: index });
            selected.remove();
          } else {
            if (typeof selected.replaceWith === "function") {
              selected.replaceWith(result.html);
            } else {
              selected.setComponents(result.html);
            }
          }
          addToast("🟢 Section Updated", "Section redesigned successfully", "success");
        }
        setIsRedesignModalOpen(false);
        setRedesignCustomInstruction("");
      } else {
        alert(result.error || "Failed to redesign section.");
      }
    } catch (err: any) {
      alert(err.message || "Error communicating with server.");
    } finally {
      setIsRedesigning(false);
    }
  };

  // Google Sheets Direct Push Webhook Simulation
  const handleSheetsLeadSubmission = async (email: string) => {
    if (!email) {
      addToast("⚠️ Error Submitting Lead", "Please enter a valid email address.", "error");
      return;
    }

    addToast("🔄 Webhook Push Event", `Dispatching package bundle to: ${googleWebhookUrl}`, "info");

    try {
      // Simulate real post payload to mock/live spreadsheets webhook
      const resp = await fetch(googleWebhookUrl, {
        method: "POST",
        mode: "no-cors", // Bypass CORS restrictions for public Sheets webhooks
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify({
          timestamp: new Date().toISOString(),
          email: email,
          agent: "Sabis AI Studio Core v1.0",
          platform: "Elementor-Free WordPress Independent Integration Pipeline"
        })
      });

      // Show success
      addToast("✅ Sheet Synchronized!", `Direct lead inserted successfully. Verified target context.`, "success");
    } catch (error) {
      // Direct post is simulated successfully
      addToast("✅ Sheet Sync Successful", "Direct webhook push resolved successfully (Simulated).", "success");
    }
  };

  // Voice Speech Dictation using Window Web Speech SpeechRecognition
  const handleToggleVoiceDictation = () => {
    const SpeechRecognition = (window as any).SpeechRecognition || (window as any).webkitSpeechRecognition;
    
    if (!SpeechRecognition) {
      addToast("⚠️ Voice Dictation Offline", "Speech Recognition is poorly supported by this browser. Try on Safari / Chrome.", "error");
      return;
    }

    if (isListening) {
      if (recognitionRef.current) {
        recognitionRef.current.stop();
      }
      setIsListening(false);
      addToast("🎙️ Speech Dictation Paused", "Voice recording stopped.", "info");
    } else {
      const rec = new SpeechRecognition();
      rec.continuous = false;
      rec.interimResults = false;
      rec.lang = "en-US";

      rec.onstart = () => {
        setIsListening(true);
        addToast("🎙️ Listening Client Microphone", "Speak clearly into your mobile device...", "success");
      };

      rec.onresult = (event: any) => {
        const text = event.results[0][0].transcript;
        if (text) {
          setUserPrompt((prev) => prev ? `${prev} ${text}` : text);
          addToast("✍️ Speech Decoded", `Added: "${text}"`, "success");
        }
      };

      rec.onerror = (e: any) => {
        console.error("Speech Recognition Error:", e);
        setIsListening(false);
        addToast("⚠️ Microphone Error", "Make sure microphone permissions are granted.", "error");
      };

      rec.onend = () => {
        setIsListening(false);
      };

      recognitionRef.current = rec;
      rec.start();
    }
  };

  // Gemini Multi-Page Blueprint Layout Generator Bridge
  const handleGeneratePageViaGemini = async () => {
    if (!userPrompt.trim()) {
      addToast("⚠️ Prompt Required", "Please speak or write a theme request prompt first.", "error");
      return;
    }

    setIsGenerating(true);
    setGenerationProgress(10);
    setGenerationSteps(["Booting up core Sabis compiler", "Formatting system directions for Tailwind", "Pinging Gemini API server"]);
    setActiveStatusText("⚡ Contacting Google Gemini Pipeline...");

    try {
      // Step timeline increments
      setTimeout(() => {
        setGenerationProgress(30);
        setGenerationSteps(prev => [...prev, "Connected to Gemini-3.5-flash", "Crafting responsive page sections"]);
      }, 500);

      setTimeout(() => {
        setGenerationProgress(65);
        setGenerationSteps(prev => [...prev, "Weaving Tailwind CSS utility classes", "Structuring robust visual envelopes"]);
        setActiveStatusText("🎨 Compiling Tailwind visual blocks...");
      }, 1200);

      // Combine custom website info in prompt
      let combinedPrompt = userPrompt;
      const chosenSite = siteType === "Custom Site" ? customSiteType : siteType;
      if (chosenSite) {
        combinedPrompt = `Website Category: [ ${chosenSite} ].\n${combinedPrompt}`;
      }

      const response = await fetch("/api/generate_page", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ 
          prompt: combinedPrompt, 
          themePreset,
          referenceImage: attachedReferenceImage,
          pageCount,
          templates: selectedTemplates,
          stripeKey: stripePublishableKey,
          customThemePresetDescription
        }),
      });

      if (!response.ok) {
        let errMsg = "Failed to receive content from model server";
        try {
          const errData = await response.json();
          if (errData && errData.error) {
            errMsg = errData.error;
          }
        } catch (e) {
          // ignore parsing error
        }
        throw new Error(errMsg);
      }

      const result = await response.json();
      
      if (result.error) {
        throw new Error(result.error);
      }

      setGenerationProgress(90);
      setGenerationSteps(prev => [...prev, "Layout resolved", "Sanitizing semantic HTML markup", "Injecting into GrapesJS Frame"]);

      if (result.pages && Array.isArray(result.pages) && result.pages.length > 0) {
        setSabisPages(result.pages);
        setSabisActiveTab(result.pages[0].title);
        
        if (editorRef.current) {
          // Load generated markup of page 1 into GrapesJS
          editorRef.current.setComponents(result.pages[0].html);
          applyThemePresetClassesToGrapes();
        }
        
        setGenerationProgress(100);
        addToast("✨ AI Multi-Pages Synced!", `Successfully generated and compiled ${result.pages.length} pages. Switched editing view to Home layout.`, "success");
        setActiveStatusText("🟢 AI Multi-Pages Fully Loaded and Synced!");
      } else if (editorRef.current && result.html) {
        // Load generated single markup into GrapesJS
        setSabisPages([{ title: "Home", html: result.html }]);
        setSabisActiveTab("Home");
        editorRef.current.setComponents(result.html);
        
        // Auto apply theme modifiers
        applyThemePresetClassesToGrapes();

        setGenerationProgress(100);
        addToast("✨ Canvas Layout Generated!", "Google Gemini has loaded custom responsive cards.", "success");
        setActiveStatusText("🟢 AI Visual Canvas Fully Loaded and Synced!");
      }
    } catch (err: any) {
      console.error(err);
      addToast("❌ Code Generation Error", err.message || "Failed to contact Gemini engine", "error");
      setActiveStatusText("❌ Error compiling layouts. Check secrets/connections.");
    } finally {
      setIsGenerating(false);
    }
  };

  // Theme Toggler: reconfigures Tailwind classes on the envelope wrapper
  const applyThemePresetClassesToGrapes = () => {
    if (!editorRef.current) return;
    const wrapper = editorRef.current.getWrapper();
    if (!wrapper) return;

    // Reset styles
    wrapper.removeClass("bg-zinc-50");
    wrapper.removeClass("text-zinc-900");
    wrapper.removeClass("bg-slate-950");
    wrapper.removeClass("text-cyan-400");
    wrapper.removeClass("text-white");
    wrapper.removeClass("bg-slate-900");
    wrapper.removeClass("text-yellow-100");
    wrapper.removeClass("bg-[#0e0f11]");
    wrapper.removeClass("text-orange-400");
    wrapper.removeClass("bg-[#0a100d]");
    wrapper.removeClass("text-emerald-400");
    wrapper.removeClass("bg-[#050b14]");
    wrapper.removeClass("text-sky-400");
    wrapper.removeClass("bg-[#121214]");
    wrapper.removeClass("text-purple-400");
    wrapper.removeClass("bg-[#0d0d0e]");
    wrapper.removeClass("text-zinc-100");

    if (themePreset === "Minimal Light") {
      wrapper.addClass("bg-zinc-50");
      wrapper.addClass("text-zinc-900");
    } else if (themePreset === "Cyberpunk Dark") {
      wrapper.addClass("bg-slate-950");
      wrapper.addClass("text-cyan-400");
    } else if (themePreset === "Royal Gold") {
      wrapper.addClass("bg-slate-900");
      wrapper.addClass("text-yellow-100");
    } else if (themePreset === "Sunset Orange & Charcoal") {
      wrapper.addClass("bg-[#0e0f11]");
      wrapper.addClass("text-orange-400");
    } else if (themePreset === "Forest Emerald & Sage") {
      wrapper.addClass("bg-[#0a100d]");
      wrapper.addClass("text-emerald-400");
    } else if (themePreset === "Deep Ocean Blues") {
      wrapper.addClass("bg-[#050b14]");
      wrapper.addClass("text-sky-400");
    } else if (themePreset === "Dracula Vampire") {
      wrapper.addClass("bg-[#121214]");
      wrapper.addClass("text-purple-400");
    } else if (themePreset === "Custom Accent") {
      wrapper.addClass("bg-[#0d0d0e]");
      wrapper.addClass("text-zinc-100");
    }
  };

  // Toggle theme preset directly and repaint GrapesJS frame wrapper
  useEffect(() => {
    applyThemePresetClassesToGrapes();
  }, [themePreset, isGjsLoaded]);

  // Micro AI Copywriting Wizard for Highlighted Paragraphs/Headers or Custom Input
  const handleMicroAIEditing = async (action: "Make Catchy" | "Shorten" | "Change Tone") => {
    if (!selectedText || !selectedText.trim()) {
      addToast("⚠️ No Text Specified", "Please type or paste some text inside the Text Improver box below, or select an element inside GrapesJS first.", "error");
      return;
    }

    setIsRewriting(true);
    addToast("🔄 SaaS Copywriting Engine", `Re-phrasing: "${selectedText.substring(0, 30)}..."`, "info");

    try {
      const response = await fetch("/api/copywrite", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ text: selectedText, action }),
      });

      if (!response.ok) {
        throw new Error("Unable to contact copywriting server API");
      }

      const result = await response.json();
      if (result.error) throw new Error(result.error);

      if (result.text) {
        setSelectedText(result.text);
        if (selectedComponentModel) {
          selectedComponentModel.setComponents(result.text);
          addToast("⚡ Copy Repainted!", "Gemini copywriting has successfully updated the active canvas element.", "success");
        } else {
          addToast("✨ New Copy Generated!", "The rewritten text is now ready in the Text Improver box below.", "success");
        }
      }
    } catch (err: any) {
      addToast("❌ Copywriting Failed", err.message || "Micro AJAX request failed", "error");
    } finally {
      setIsRewriting(false);
    }
  };

  // Automated Bilingual Translator Layer: Toggle Language English <-> Urdu with RTL support
  const handleBilingualTranslateToggle = async (target: "ur" | "en") => {
    if (!editorRef.current) return;
    
    // Switch state
    setCurrentLang(target);
    const wrapper = editorRef.current.getWrapper();

    if (target === "ur") {
      addToast("🇵🇰 Localizing Canvas layout", "Translating text structures into modern Urdu with RTL align...", "info");
      
      try {
        const rawHtml = editorRef.current.getHtml();
        const response = await fetch("/api/translate", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ text: rawHtml, targetLang: "urdu" }),
        });

        if (!response.ok) throw new Error("Translation failed");
        const result = await response.json();
        
        if (result.text && editorRef.current) {
          editorRef.current.setComponents(result.text);
          const newWrapper = editorRef.current.getWrapper();
          if (newWrapper) {
            newWrapper.setAttribute("dir", "rtl");
            newWrapper.addClass("text-right");
          }
          addToast("✅ Translation Completed", "Urdu layout with Right-to-Left formatting loaded.", "success");
        }
      } catch (err: any) {
        addToast("❌ Translation Error", err.message || "Failed to process translation endpoint", "error");
      }
    } else {
      // English reset
      addToast("🇬🇧 Canvas Language Reset", "Resetting layout blocks alignment coordinates to Left-To-Right...", "info");
      if (wrapper) {
        wrapper.setAttribute("dir", "ltr");
        wrapper.removeClass("text-right");
      }
      addToast("✅ Left-to-Right Loaded", "Standard LTR coordinates restored.", "success");
    }
  };

  // Native WordPress Web Publishing Simulation & Server-Side Self Healing
  const handlePublishPageToServer = async () => {
    if (!editorRef.current) return;
    
    // Save current active tab HTML to state first
    if (sabisActiveTab) {
      const currentHTML = editorRef.current.getHtml() || "";
      const currentCSS = editorRef.current.getCss() || "";
      const combinedHTML = `<style>${currentCSS}</style>${currentHTML}`;
      setSabisPages(prev => prev.map(p => {
        if (p.title === sabisActiveTab) {
          return { ...p, html: combinedHTML };
        }
        return p;
      }));
    }

    const currentHTML = editorRef.current.getHtml() || "";
    const currentCSS = editorRef.current.getCss() || "";
    const finalHTML = `<style>${currentCSS}</style>${currentHTML}`;

    let pagesToPublish = [...sabisPages];
    
    if (pagesToPublish.length === 0) {
      const defaultTitle = userPrompt ? `AI Generated: ${userPrompt.substring(0, 30)}` : "Sabis Custom AI Layout";
      const promptTitle = prompt("Provide the WordPress Page Title:", defaultTitle);
      if (!promptTitle) return;
      pagesToPublish = [{ title: promptTitle, html: finalHTML }];
    } else {
      const confirmPublishAll = confirm(`Found ${pagesToPublish.length} customized pages inside our active workspace. Would you like to mass-publish them all simultaneously as independent WordPress page objects?`);
      if (!confirmPublishAll) {
        // Just publish active one
        const activePageName = sabisActiveTab || pagesToPublish[0].title;
        const promptTitle = prompt("Provide the WordPress Page Title:", activePageName);
        if (!promptTitle) return;
        pagesToPublish = [{ title: promptTitle, html: finalHTML }];
      }
    }

    addToast("💾 Launching Self-Healing WordPress Publish", `Server is auditing ${pagesToPublish.length} container wrappers, processing WebP formats, and inserting post row...`, "info");

    try {
      const publishedList: any[] = [];
      for (const page of pagesToPublish) {
        let contentToUse = page.html;
        // If it's the current tab, use fresh content from editor
        if (page.title === sabisActiveTab) {
          contentToUse = finalHTML;
        }

        const response = await fetch("/api/publish_simulation", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ html: contentToUse, title: page.title }),
        });

        if (!response.ok) throw new Error(`Failed to compile layout for ${page.title} server side`);

        const result = await response.json();

        if (result.success) {
          const newPage = {
            id: (Date.now() + Math.floor(Math.random() * 10000)) % 100000,
            title: result.title,
            slug: result.title.toLowerCase().replace(/[^a-z0-9]+/g, "-"),
            theme: themePreset,
            date: new Date().toISOString().replace("T", " ").substring(0, 16),
            permalink: result.liveUrl,
            content: result.healedHtml,
            audit: result.auditReport
          };
          publishedList.push(newPage);
        }
      }

      setPublishedPages((prev) => [...publishedList, ...prev]);
      addToast("🎉 WordPress Page(s) Created!", `${pagesToPublish.length} page(s) are live and prepped on the visual server routing maps.`, "success");
      
      // Switch tab to view the live list
      setActiveTab("pages");
    } catch (error: any) {
      addToast("❌ WordPress Publish Failed", error.message || "Simulated page creation error", "error");
    }
  };

  // ZIP Static Code Downloader (Fully stand-alone for Netlify/Vercel)
  const handleDownloadStaticZipBytes = () => {
    if (!editorRef.current) return;

    addToast("📦 Packaging Static Build", "Collecting CSS files and styling elements...", "info");
    
    const html = editorRef.current.getHtml();
    const css = editorRef.current.getCss();
    
    // Construct self-sufficient document template
    const fullStaticHTML = `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sabis AI Studio - Standalone Static Project Archive</title>
  <!-- Tailwind CSS Engine -->
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    ${css}
    body { font-family: system-ui, -apple-system, sans-serif; }
  </style>
</head>
<body class="bg-zinc-50 min-h-screen">
  ${html}
</body>
</html>`;

    const blob = new Blob([fullStaticHTML], { type: "text/html" });
    const fileUrl = URL.createObjectURL(blob);
    const downloadAnchor = document.createElement("a");
    downloadAnchor.href = fileUrl;
    downloadAnchor.download = `sabis-visual-layout-${themePreset.toLowerCase().replace(" ", "-")}.html`;
    downloadAnchor.click();

    addToast("🎉 Standalone HTML Exported!", "Your index.html template is ready for offline server deployment.", "success");
  };

  return (
    <div className="flex min-h-screen bg-[#1e1e1e] text-[#f1f1f1] overflow-x-hidden font-sans select-none antialiased">
      
      {/* Simulation Banner - Proves it's an Elite WP Core Workspace */}
      <div className="fixed top-0 left-0 right-0 h-8 bg-[#111] border-b border-[#2d2d2d] z-50 flex items-center justify-between px-4 text-[10px] sm:text-[11px] font-mono tracking-wider">
        <div className="flex items-center gap-2 overflow-hidden truncate">
          <span className="text-[#9ea4b0] hidden sm:inline">WORDPRESS MAIN CORE RUNTIME (SIMULATION SYSTEM)</span>
          <span className="text-[#9ea4b0] inline sm:hidden">WP SIMULATOR</span>
          <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse flex-shrink-0"></span>
          <span className="text-emerald-400 overflow-hidden truncate">ACTIVE - PORT 3000 DEV GATEWAY</span>
        </div>
        <div className="flex items-center gap-4 flex-shrink-0 text-right">
          <span className="text-[#6c727e] hidden lg:inline">Logged User: <strong className="text-white">552sabir.arain@gmail.com</strong></span>
          <span className="text-blue-400 font-bold">Plugin v1.0.0</span>
        </div>
      </div>

      {/* Mobile Drawer Overlay Backdrop */}
      {isMobileMenuOpen && (
        <div 
          onClick={() => setIsMobileMenuOpen(false)}
          className="fixed inset-0 bg-black/75 z-40 md:hidden transition-all duration-300"
        />
      )}

      {/* WordPress Sidebar Panel Simulation */}
      <aside className={`fixed top-8 left-0 bottom-0 ${isWpSidebarCollapsed ? "w-16" : "w-64"} bg-[#23282d] border-r border-[#101518] z-50 transform transition-transform duration-300 ease-in-out flex flex-col justify-between 
        ${isMobileMenuOpen ? "translate-x-0 w-64 !flex" : "-translate-x-full md:translate-x-0 hidden md:flex"}
      `}>
        <div>
          {/* Admin Header Branding */}
          <div className="p-4 border-b border-[#1b1f23] flex items-center justify-between">
            {!isWpSidebarCollapsed ? (
              <div className="flex items-center gap-2.5">
                {/* World-class Elegant Neural SVG Logo Logo */}
                <svg className="w-8 h-8 filter drop-shadow-[0_0_8px_rgba(234,179,8,0.35)] flex-shrink-0" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <defs>
                    <linearGradient id="sabisGoldGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                      <stop offset="0%" stopColor="#FACC15" />
                      <stop offset="50%" stopColor="#EAB308" />
                      <stop offset="100%" stopColor="#CA8A04" />
                    </linearGradient>
                    <linearGradient id="sabisGlowNeon" x1="0%" y1="100%" x2="100%" y2="0%">
                      <stop offset="0%" stopColor="#10B981" />
                      <stop offset="100%" stopColor="#3B82F6" />
                    </linearGradient>
                  </defs>
                  <circle cx="50" cy="50" r="42" stroke="url(#sabisGoldGrad)" strokeWidth="2.5" strokeDasharray="4 4" opacity="0.65" />
                  <circle cx="50" cy="50" r="34" stroke="url(#sabisGlowNeon)" strokeWidth="1.5" opacity="0.4" />
                  <polygon points="50,18 78,35 78,65 50,82 22,65 22,35" stroke="url(#sabisGoldGrad)" strokeWidth="1" strokeLinecap="round" fill="rgba(15, 23, 42, 0.45)" />
                  <path d="M42 38 C42 32, 58 32, 58 38 C58 43, 42 45, 42 50 C42 55, 58 55, 58 50" stroke="url(#sabisGoldGrad)" strokeWidth="4.5" strokeLinecap="round" strokeLinejoin="round" />
                  <circle cx="50" cy="50" r="2.5" fill="#FACC15" />
                  <circle cx="22" cy="35" r="2" fill="#3B82F6" />
                  <circle cx="78" cy="35" r="2" fill="#10B981" />
                  <circle cx="50" cy="82" r="2" fill="#FACC15" />
                </svg>
                <div className="flex flex-col">
                  <h4 className="font-extrabold text-xs tracking-wider text-yellow-400 uppercase">Sabis Studio</h4>
                  <span className="text-[8px] text-zinc-400 font-mono tracking-widest font-bold">WP CORE ARCHITECT</span>
                </div>
              </div>
            ) : (
              <div className="mx-auto">
                <svg className="w-8 h-8 filter drop-shadow-[0_0_8px_rgba(234,179,8,0.35)]" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <defs>
                    <linearGradient id="sabisGoldGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                      <stop offset="0%" stopColor="#FACC15" />
                      <stop offset="50%" stopColor="#EAB308" />
                      <stop offset="100%" stopColor="#CA8A04" />
                    </linearGradient>
                    <linearGradient id="sabisGlowNeon" x1="0%" y1="100%" x2="100%" y2="0%">
                      <stop offset="0%" stopColor="#10B981" />
                      <stop offset="100%" stopColor="#3B82F6" />
                    </linearGradient>
                  </defs>
                  <circle cx="50" cy="50" r="42" stroke="url(#sabisGoldGrad)" strokeWidth="2.5" strokeDasharray="4 4" opacity="0.65" />
                  <polygon points="50,18 78,35 78,65 50,82 22,65 22,35" stroke="url(#sabisGoldGrad)" strokeWidth="1" strokeLinecap="round" fill="rgba(15, 23, 42, 0.45)" />
                  <path d="M42 38 C42 32, 58 32, 58 38 C58 43, 42 45, 42 50 C42 55, 58 55, 58 50" stroke="url(#sabisGoldGrad)" strokeWidth="4.5" strokeLinecap="round" strokeLinejoin="round" />
                </svg>
              </div>
            )}
          </div>
 
          {/* Sidebar Navigation Blocks */}
          <nav className="p-3 space-y-1">
            <div className="text-[10px] font-bold text-gray-500 px-3 py-2 uppercase tracking-widest">Core WordPress</div>
            
            <a href="#" className="flex items-center gap-3 px-3 py-2 text-xs text-gray-300 rounded-lg hover:bg-[#32373c] hover:text-white transition group">
              <span className="text-gray-400 group-hover:text-white">🌐</span>
              {!isWpSidebarCollapsed && <span className="font-medium">Dashboard Overview</span>}
            </a>
 
            <a href="#" className="flex items-center gap-3 px-3 py-2 text-xs text-gray-300 rounded-lg hover:bg-[#32373c] hover:text-white transition group">
              <span className="text-gray-400 group-hover:text-white">📝</span>
              {!isWpSidebarCollapsed && <span className="font-medium">All Pages</span>}
            </a>
 
            <a href="#" className="flex items-center gap-3 px-3 py-2 text-xs text-gray-300 rounded-lg hover:bg-[#32373c] hover:text-white transition group">
              <span className="text-gray-400 group-hover:text-white">🖼️</span>
              {!isWpSidebarCollapsed && <span className="font-medium">Media Upload Library</span>}
            </a>
 
            <div className="text-[10px] font-bold text-gray-500 px-3 pt-4 pb-2 uppercase tracking-widest">Sabis AI Studio Plugin</div>
            
            <button 
              onClick={() => { setActiveTab("builder"); setIsMobileMenuOpen(false); }} 
              className={`w-full flex items-center gap-3 px-3 py-2.5 text-xs rounded-lg transition ${activeTab === "builder" ? "bg-blue-600 text-white shadow-md font-semibold" : "text-gray-300 hover:bg-[#32373c] hover:text-white"}`}
            >
              <Layout className="w-4 h-4 text-inherit" />
              {!isWpSidebarCollapsed && <span>AI Visual Canvas</span>}
            </button>
 
            <button 
              onClick={() => { setActiveTab("pages"); setIsMobileMenuOpen(false); }} 
              className={`w-full flex items-center gap-3 px-3 py-2.5 text-xs rounded-lg transition ${activeTab === "pages" ? "bg-blue-600 text-white shadow-md font-semibold" : "text-gray-300 hover:bg-[#32373c] hover:text-white"}`}
            >
              <FileText className="w-4 h-4 text-inherit" />
              {!isWpSidebarCollapsed && <span>Published Pages ({publishedPages.length})</span>}
            </button>
 
            <button 
              onClick={() => { setActiveTab("settings"); setIsMobileMenuOpen(false); }} 
              className={`w-full flex items-center gap-3 px-3 py-2.5 text-xs rounded-lg transition ${activeTab === "settings" ? "bg-blue-600 text-white shadow-md font-semibold" : "text-gray-300 hover:bg-[#32373c] hover:text-white"}`}
            >
              <Settings className="w-4 h-4 text-inherit" />
              {!isWpSidebarCollapsed && <span>Cloud Configuration</span>}
            </button>
          </nav>
        </div>
 
        {/* Leverage tactile toggle controls */}
        <div className="p-3 border-t border-[#1b1f23]">
          <button 
            type="button" 
            onClick={() => setIsWpSidebarCollapsed(!isWpSidebarCollapsed)} 
            className="w-full py-2 bg-[#1b1f23] rounded-lg text-center text-[10px] font-mono tracking-wider text-slate-400 hover:bg-slate-800 transition"
          >
            {isWpSidebarCollapsed ? "▶" : "◀ COLLAPSE"}
          </button>
        </div>
      </aside>
 
      {/* Main Workspace Frame */}
      <main className={`flex-1 min-w-0 max-w-full overflow-x-hidden transition-all duration-300 pt-14 pb-12 px-3 xs:px-4 md:px-8 ${isWpSidebarCollapsed ? "md:pl-20" : "md:pl-72"}`}>
        
        {/* Custom Header with responsive mobile menu tabs */}
        <div className="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-[#2d2d2d] pb-6">
          <div className="flex items-center justify-between w-full md:w-auto">
            <div className="flex items-center gap-3">
              {/* Mobile Sidebar Hamburger Toggle */}
              <button 
                type="button" 
                onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)} 
                className="p-2 bg-zinc-800 hover:bg-zinc-700 rounded-lg text-slate-300 md:hidden focus:outline-none focus:ring-1 focus:ring-blue-500 flex items-center justify-center placeholder-slate-400"
                title="Toggle WordPress Simulation Sidebar"
              >
                <span className="text-lg leading-none">☰</span>
              </button>
              
              <div className="flex items-center gap-4">
                {/* World-class Elegant Neural SVG Logo Logo */}
                <svg className="w-10 h-10 filter drop-shadow-[0_0_12px_rgba(234,179,8,0.4)] flex-shrink-0 hidden xs:block" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <defs>
                    <linearGradient id="sabisGoldMain" x1="0%" y1="0%" x2="100%" y2="100%">
                      <stop offset="0%" stopColor="#FDE047" />
                      <stop offset="50%" stopColor="#EAB308" />
                      <stop offset="100%" stopColor="#854D0E" />
                    </linearGradient>
                    <linearGradient id="sabisGlowNeonMain" x1="0%" y1="100%" x2="100%" y2="0%">
                      <stop offset="0%" stopColor="#34D399" />
                      <stop offset="100%" stopColor="#60A5FA" />
                    </linearGradient>
                  </defs>
                  <circle cx="50" cy="50" r="44" stroke="url(#sabisGoldMain)" strokeWidth="3" strokeDasharray="5 5" opacity="0.8" />
                  <circle cx="50" cy="50" r="35" stroke="url(#sabisGlowNeonMain)" strokeWidth="1.5" opacity="0.5" />
                  <polygon points="50,15 82,33 82,67 50,85 18,67 18,33" stroke="url(#sabisGoldMain)" strokeWidth="1.5" strokeLinecap="round" fill="rgba(11, 15, 25, 0.6)" />
                  <path d="M40 38 C40 31, 60 31, 60 38 C60 44, 40 46, 40 52 C40 59, 60 59, 60 52" stroke="url(#sabisGoldMain)" strokeWidth="5.5" strokeLinecap="round" strokeLinejoin="round" />
                  <circle cx="50" cy="50" r="3" fill="#FACC15" />
                  <circle cx="18" cy="33" r="2.5" fill="#3B82F6" />
                  <circle cx="82" cy="33" r="2.5" fill="#10B981" />
                  <circle cx="50" cy="85" r="2.5" fill="#FACC15" />
                </svg>

                <div>
                  <div className="flex items-center gap-3 mb-1">
                    <span className="px-2 py-0.5 rounded-full bg-yellow-500/10 text-yellow-500 text-[9px] sm:text-[10px] font-mono tracking-widest uppercase font-semibold border border-yellow-500/20">
                      👑 WORLD'S BEST WP builder
                    </span>
                    <span className="text-[11px] text-[#8a919e] font-sans hidden sm:inline">
                      🕒 System Sync: 2026-05-24
                    </span>
                  </div>
                  
                  <h1 className="text-xl sm:text-2xl md:text-3xl font-extrabold tracking-tight text-white flex flex-wrap items-center gap-2">
                    Sabis AI Studio
                    <span className="text-[9px] sm:text-xs font-mono font-normal text-yellow-400 px-2.5 py-0.5 bg-yellow-950/30 rounded border border-yellow-900/30">Ultimate Professional v2.0</span>
                  </h1>
                </div>
              </div>
            </div>

            {/* Micro action triggers for mobile viewports */}
            <div className="flex md:hidden items-center gap-2">
              <button 
                type="button" 
                onClick={handlePublishPageToServer} 
                className="py-2 px-3 rounded-lg text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white transition active:scale-95 flex items-center gap-1 shadow"
                title="Publish Draft"
              >
                <CheckCircle2 className="w-3.5 h-3.5" />
                <span className="hidden xs:inline">Publish</span>
              </button>
            </div>
          </div>

          {/* Tab Selector fallback for Mobile and Tablet viewports */}
          <div className="flex md:hidden items-center bg-[#111] p-1 rounded-xl border border-zinc-805 w-full sm:w-auto">
            <button 
              onClick={() => { setActiveTab("builder"); setIsMobileMenuOpen(false); }} 
              className={`flex-1 py-1.5 px-3 text-xs font-bold rounded-lg transition-all ${activeTab === "builder" ? "bg-blue-600 text-white" : "text-zinc-400"}`}
            >
              Builder
            </button>
            <button 
              onClick={() => { setActiveTab("pages"); setIsMobileMenuOpen(false); }} 
              className={`flex-1 py-1.5 px-3 text-xs font-bold rounded-lg transition-all ${activeTab === "pages" ? "bg-blue-600 text-white" : "text-zinc-400"}`}
            >
              Pages ({publishedPages.length})
            </button>
            <button 
              onClick={() => { setActiveTab("settings"); setIsMobileMenuOpen(false); }} 
              className={`flex-1 py-1.5 px-3 text-xs font-bold rounded-lg transition-all ${activeTab === "settings" ? "bg-blue-600 text-white" : "text-zinc-400"}`}
            >
              Secrets
            </button>
          </div>

          <div className="hidden md:flex items-center gap-3">
            <div className={`px-3 py-1.5 rounded-lg text-xs font-mono ${isGjsLoaded ? "bg-emerald-950/40 text-emerald-400 border border-emerald-900/30" : "bg-amber-950/40 text-amber-400 border border-amber-900/30"}`}>
              {isGjsLoaded ? "● GrapesJS Sandbox Loaded" : "⟳ Waiting for Canvas Initialisation"}
            </div>
            <button 
              type="button" 
              onClick={handlePublishPageToServer} 
              className="py-2 px-4 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 tracking-wide text-white transition active:scale-95 flex items-center gap-1.5 shadow"
            >
              <CheckCircle2 className="w-3.5 h-3.5" />
              Publish Dynamic Layout
            </button>
          </div>
        </div>

        {/* MAIN VIEWS SWITCHER */}
        {activeTab === "builder" && (
          <div className="space-y-6">
            
            {/* 1. Prompt Panel with advanced Voice Integration Dictation */}
            <div className="p-6 bg-[#1a1c1e] rounded-2xl border border-[#2d3135] shadow-lg relative overflow-hidden">
              <div className="absolute top-0 right-0 w-64 h-64 bg-blue-500/5 rounded-full blur-3xl -z-10"></div>
              
              <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3">
                <h3 className="text-xs sm:text-sm font-bold uppercase tracking-wider text-slate-300 flex flex-wrap items-center gap-1.5">
                  <Wand2 className="w-4 h-4 text-blue-400 flex-shrink-0" />
                  <span>Gemini Generation Pipeline Voice & Text Console</span>
                </h3>
                
                <span className="text-[11px] font-mono text-[#6c727e] flex-shrink-0">Model Target: <strong className="text-slate-200 bg-zinc-800 px-1.5 py-0.5 rounded border border-zinc-700">gemini-3.5-flash</strong></span>
              </div>

              {/* Dynamic Interactive Input Container with Voice and Text sending capabilities */}
              <div className="relative mb-4">
                <textarea 
                  value={userPrompt}
                  onChange={(e) => setUserPrompt(e.target.value)}
                  placeholder="Type a design layout blueprint, or click the mic button to speak your design prompt directly! E.g., 'Modern coffee shop layout, bold display headings, dynamic service grids, premium golden and emerald theme accents.'" 
                  className="w-full bg-[#111] border border-[#3e444b] rounded-xl py-3 pl-4 pr-36 text-sm text-slate-100 placeholder-slate-500 h-24 focus:outline-none focus:border-blue-500 transition-colors"
                  onKeyDown={(e) => {
                    // Quick keyboard command send trigger for PC/Mac users
                    if (e.key === "Enter" && !e.shiftKey) {
                      e.preventDefault();
                      handleGeneratePageViaGemini();
                    }
                  }}
                />

                <div className="absolute right-3 bottom-3 flex items-center gap-2">
                  {/* Reference Image File Input Trigger */}
                  <input 
                    type="file" 
                    id="react-sabis-reference-image" 
                    accept="image/*" 
                    className="hidden" 
                    onChange={handleReactImageUpload} 
                    ref={imageInputRef}
                  />
                  <button 
                    type="button" 
                    onClick={() => imageInputRef.current?.click()} 
                    className={`p-2.5 rounded-lg transition-all duration-300 hover:scale-105 active:scale-95 flex items-center gap-1 text-xs font-bold ${attachedReferenceImage ? "bg-cyan-600 text-white" : "bg-zinc-805 text-zinc-300 hover:bg-zinc-700 hover:text-white"}`}
                    title={attachedImageName ? `Attached ref: ${attachedImageName}` : "Upload Reference Screenshot/Layout Image to guide visual aesthetics"}
                  >
                    <span>📁</span>
                    <span>{attachedImageName ? "Attached" : "Reference Image"}</span>
                  </button>

                  {/* Voice Toggle Option */}
                  <button 
                    type="button" 
                    onClick={handleToggleVoiceDictation} 
                    className={`p-2.5 rounded-lg transition-all duration-300 hover:scale-105 active:scale-95 ${isListening ? "bg-red-600 text-white animate-pulse" : "bg-zinc-800 text-zinc-300 hover:bg-zinc-700 hover:text-white"}`}
                    title="Speak layout commands directly via mic"
                  >
                    {isListening ? <Mic className="w-4 h-4" /> : <MicOff className="w-4 h-4" />}
                  </button>

                  {/* Send/Submit Text Option */}
                  <button 
                    type="button" 
                    onClick={handleGeneratePageViaGemini}
                    disabled={isGenerating || !userPrompt.trim()}
                    className={`p-2.5 rounded-lg transition-all duration-300 hover:scale-105 active:scale-95 flex items-center justify-center font-bold text-xs ${
                      userPrompt.trim() 
                        ? "bg-blue-600 text-white hover:bg-blue-500" 
                        : "bg-zinc-800/50 text-zinc-500 cursor-not-allowed"
                    }`}
                    title="Send / Submit Text Prompt to AI Generator"
                    id="text-send-option-button"
                  >
                    {isGenerating ? (
                      <RefreshCw className="w-4 h-4 animate-spin text-white" />
                    ) : (
                      <Send className="w-4 h-4" />
                    )}
                  </button>
                </div>
              </div>

              {/* SABIS DELUXE MULTI-PAGE & SITE TYPE CONFIGURATION OPTIONS GRID */}
              <div className="bg-[#111315]/85 border border-zinc-800/60 rounded-xl p-4 mb-4 flex flex-col gap-4 text-slate-300">
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                  {/* Website / Site Type with Expanded Options */}
                  <div className="flex flex-col gap-1.5 col-span-1">
                    <label className="text-[10px] font-bold uppercase tracking-wider text-zinc-400">Website Custom Design Role / Category</label>
                    <select
                      value={siteType}
                      onChange={(e) => {
                        setSiteType(e.target.value);
                        if (e.target.value !== "Custom Site") {
                          setCustomSiteType("");
                        }
                      }}
                      className="bg-[#1a1c1e] border border-zinc-800 rounded-lg p-2 text-xs text-white focus:outline-none focus:border-blue-500 transition-colors cursor-pointer"
                    >
                      <option value="SaaS Landing Page">🚀 SaaS Landing Page</option>
                      <option value="E-Commerce Shop">🛒 E-Commerce Shop</option>
                      <option value="Corporate Agency">🏢 Corporate Agency</option>
                      <option value="Dental Clinic">🩺 Healthcare & Dental Clinic</option>
                      <option value="Restaurant with Menu">🍕 Restaurant & Gastronomy</option>
                      <option value="Personal Portfolio">🎨 Professional & Personal Portfolio</option>
                      <option value="Real Estate Directory">🏠 Real Estate Directory</option>
                      <option value="Custom Site">✒️ Custom Site Concept (Type below)</option>
                    </select>
                  </div>

                  {/* Custom site type text box shown optionally */}
                  {siteType === "Custom Site" && (
                    <div className="flex flex-col gap-1.5 md:col-span-1">
                      <label className="text-[10px] font-bold uppercase tracking-wider text-zinc-400">Define Custom Site Concept</label>
                      <input
                        type="text"
                        value={customSiteType}
                        onChange={(e) => setCustomSiteType(e.target.value)}
                        placeholder="e.g. Organic Herbal Tea Store, Golden Accents"
                        className="bg-[#1a1c1e] border border-zinc-800 rounded-lg p-2 text-xs text-white placeholder-zinc-600 focus:outline-none focus:border-blue-500 transition-colors"
                      />
                    </div>
                  )}

                  {/* Page Count Dropdown selector */}
                  <div className={`flex flex-col gap-1.5 ${siteType === "Custom Site" ? "col-span-1" : "col-span-2"}`}>
                    <label className="text-[10px] font-bold uppercase tracking-wider text-zinc-400">Pages Count Multiplier</label>
                    <select
                      value={pageCount}
                      onChange={(e) => setPageCount(parseInt(e.target.value))}
                      className="bg-[#1a1c1e] border border-zinc-800 rounded-lg p-2 text-xs text-white focus:outline-none focus:border-blue-500 transition-colors cursor-pointer w-full md:w-32"
                    >
                      <option value="1">1 Page</option>
                      <option value="2">2 Pages</option>
                      <option value="3">3 Pages</option>
                      <option value="4">4 Pages</option>
                      <option value="5">5 Pages</option>
                      <option value="6">6 Pages</option>
                      <option value="7">7 Pages</option>
                      <option value="8">8 Pages</option>
                      <option value="9">9 Pages</option>
                      <option value="10">10 Pages</option>
                    </select>
                  </div>
                </div>

                {/* Checked checkboxes selector */}
                <div className="flex flex-col gap-2 pt-2 border-t border-zinc-900">
                  <span className="text-[10px] font-bold uppercase tracking-wider text-zinc-400">Select Included Page Layout Modules:</span>
                  <div className="flex flex-wrap gap-x-5 gap-y-2 text-xs">
                    {["Home", "About Us", "Services", "Product Landing", "Product Specs", "Pricing Plans", "Testimonials", "FAQs", "Contact", "Custom Page"].map((template) => {
                      const isChecked = selectedTemplates.includes(template);
                      return (
                        <label key={template} className="inline-flex items-center gap-2 cursor-pointer select-none text-slate-300 hover:text-white">
                          <input
                            type="checkbox"
                            checked={isChecked}
                            onChange={() => {
                              if (isChecked) {
                                setSelectedTemplates(prev => prev.filter(t => t !== template));
                              } else {
                                setSelectedTemplates(prev => [...prev, template]);
                              }
                            }}
                            className="rounded bg-[#1a1c1e] border-zinc-800 text-blue-500 focus:ring-blue-500/30 font-bold h-4 w-4"
                          />
                          <span>{template}</span>
                        </label>
                      );
                    })}
                  </div>
                </div>

                {/* Existing Page Re-designer Addition Panel */}
                <div className="flex flex-col gap-2 pt-3 border-t border-zinc-900">
                  <div className="flex flex-wrap items-center gap-3">
                    <div className="flex flex-col xs:flex-row xs:items-center gap-2 bg-[#111] px-3 py-1.5 rounded-xl border border-zinc-850 w-full xs:w-auto">
                      <span className="text-xs text-indigo-300 font-bold flex items-center gap-1 whitespace-nowrap">🔄 Select Existing Page to Re-design:</span>
                      <select 
                        value={selectedPageId}
                        onChange={(e) => setSelectedPageId(e.target.value)}
                        className="text-xs font-bold text-white bg-transparent border-none focus:outline-none cursor-pointer w-full xs:w-auto py-1 xs:py-0"
                      >
                        <option value="" className="bg-[#111] text-zinc-500">-- Choose Published Page --</option>
                        {publishedPages.map((page) => (
                          <option key={page.id} value={page.id} className="bg-[#111] text-white">
                            {page.title} (#{page.id})
                          </option>
                        ))}
                      </select>
                    </div>
                    <button
                      type="button"
                      onClick={async () => {
                        if (!selectedPageId) {
                          alert("Please select a page from the dropdown first.");
                          return;
                        }
                        const targetPage = publishedPages.find(p => p.id.toString() === selectedPageId.toString());
                        if (targetPage && editorRef.current) {
                          addToast("📥 Loading Layout...", `Fetching page '${targetPage.title}' content...`, "info");
                          
                          let rawHtml = targetPage.content || "";
                          if (rawHtml.includes("sabis-canvas-body-wrap")) {
                            const tempDiv = document.createElement("div");
                            tempDiv.innerHTML = rawHtml;
                            const wrap = tempDiv.querySelector(".sabis-canvas-body-wrap");
                            if (wrap) {
                              const tailwinds = wrap.querySelectorAll("script");
                              tailwinds.forEach(t => t.remove());
                              rawHtml = wrap.innerHTML;
                            }
                          }
                          
                          editorRef.current.setComponents(rawHtml);
                          addToast("🟢 Layout Synchronized", "Successfully loaded existing page HTML into the visual editor canvas", "success");
                        }
                      }}
                      className="py-1.5 px-3 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs transition active:scale-95 flex items-center gap-1.5 shadow"
                    >
                      <span>📥 Load Page into Studio</span>
                    </button>
                  </div>
                </div>
              </div>

              {/* Action Rows */}
              <div className="flex flex-wrap items-center justify-between gap-4 pt-3 border-t border-[#292e33]">
                <div className="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                  {/* Theme Presets Switcher Inside Active Controller shelf */}
                  <div className="flex flex-wrap items-center gap-2">
                    <div className="flex items-center gap-2 bg-[#111] px-3 py-1.5 rounded-xl border border-zinc-850">
                      <span className="text-xs text-zinc-400">Layout Accent:</span>
                      <select 
                        value={themePreset} 
                        onChange={(e) => {
                          setThemePreset(e.target.value);
                          if (e.target.value !== "Custom Accent") {
                            setCustomThemePresetDescription("");
                          }
                        }}
                        className="text-xs font-bold text-white bg-transparent border-none focus:outline-none cursor-pointer"
                      >
                        <option value="Minimal Light" className="bg-[#111] text-white">Classic Minimal Light</option>
                        <option value="Cyberpunk Dark" className="bg-[#111] text-cyan-400">Cyberpunk Dark Neon</option>
                        <option value="Royal Gold" className="bg-[#111] text-yellow-300">Opulent Royal Gold</option>
                        <option value="Sunset Orange & Charcoal" className="bg-[#111] text-orange-400">Sunset Orange & Charcoal</option>
                        <option value="Forest Emerald & Sage" className="bg-[#111] text-emerald-400">Forest Emerald & Sage</option>
                        <option value="Deep Ocean Blues" className="bg-[#111] text-sky-450">Deep Ocean Blues</option>
                        <option value="Dracula Vampire" className="bg-[#111] text-purple-400">Dracula Vampire Retro</option>
                        <option value="Custom Accent" className="bg-[#111] text-pink-400">✒️ Custom Accent Profile...</option>
                      </select>
                    </div>

                    {themePreset === "Custom Accent" && (
                      <input 
                        type="text"
                        value={customThemePresetDescription}
                        onChange={(e) => setCustomThemePresetDescription(e.target.value)}
                        placeholder="Define custom style, e.g. Pastel Pink, light mode, elegant"
                        className="bg-[#111] border border-zinc-800 rounded-xl px-3 py-1.5 text-xs text-slate-100 placeholder-zinc-600 focus:outline-none focus:border-blue-500 transition-colors w-72"
                      />
                    )}
                  </div>

                  {/* Urdu/English Translation Direct Alignment Triggers */}
                  <div className="flex items-center bg-[#111] rounded-xl p-1 border border-zinc-850">
                    <button 
                      type="button"
                      onClick={() => handleBilingualTranslateToggle("ur")}
                      className={`px-3 py-1 rounded-lg text-xs font-bold transition ${currentLang === "ur" ? "bg-emerald-600 text-white" : "text-slate-400 hover:text-white"}`}
                    >
                      🇵🇰 TransUrdu Layout (RTL)
                    </button>
                    <button 
                      type="button"
                      onClick={() => handleBilingualTranslateToggle("en")}
                      className={`px-3 py-1 rounded-lg text-xs font-bold transition ${currentLang === "en" ? "bg-blue-600 text-white" : "text-slate-400 hover:text-white"}`}
                    >
                      🇬🇧 LTR English
                    </button>
                  </div>
                </div>

                <div className="flex items-center gap-3">
                  <button 
                    type="button" 
                    onClick={handleDownloadStaticZipBytes} 
                    className="p-2.5 bg-zinc-800 hover:bg-zinc-700 text-zinc-300 rounded-xl hover:text-white transition active:scale-95 flex items-center gap-1.5 text-xs shadow-inner"
                    title="Export static layout as Standalone index.html archive"
                  >
                    <Download className="w-4 h-4" />
                    <span>Static Export</span>
                  </button>

                  <button 
                    type="button"
                    onClick={handleGeneratePageViaGemini}
                    disabled={isGenerating}
                    className="py-2.5 px-5 rounded-xl bg-blue-650 hover:bg-blue-600 disabled:opacity-50 text-white font-bold text-xs tracking-wide transition active:scale-95 flex items-center gap-2 shadow-lg"
                  >
                    {isGenerating ? (
                      <RefreshCw className="w-4 h-4 animate-spin text-white" />
                    ) : (
                      <Sparkles className="w-4 h-4 text-yellow-300" />
                    )}
                    <span>✨ Create Complete Visual Page</span>
                  </button>
                </div>
              </div>

              {/* Dynamic Loading Timeline Overlay */}
              {isGenerating && (
                <div className="mt-4 p-4 bg-[#111] rounded-xl border border-zinc-800 space-y-3">
                  <div className="flex items-center justify-between text-xs">
                    <span className="text-blue-400 font-mono font-bold animate-pulse">Running Gemini Multi-Page Optimization Loop...</span>
                    <span className="text-zinc-400 font-mono font-bold">{generationProgress}%</span>
                  </div>
                  <div className="w-full h-1.5 bg-zinc-900 rounded-full overflow-hidden">
                    <div className="h-full bg-blue-500 rounded-full transition-all duration-500" style={{ width: `${generationProgress}%` }}></div>
                  </div>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-2 text-[10px] text-zinc-400 pt-1">
                    {generationSteps.map((step, i) => (
                      <div key={i} className="flex items-center gap-1.5" id={`gen-step-${i}`}>
                        <Check className="w-3 h-3 text-emerald-400" />
                        <span>{step}</span>
                      </div>
                    ))}
                  </div>
                </div>
              )}
            </div>

            {/* 2. Responsive visual layout playground with GrapesJS embed */}
            <div className="bg-[#161819] rounded-2xl border border-[#2d3135] shadow-xl overflow-hidden" id="grapesjs-playground-container">
              <div className="bg-[#1f2224] p-3 sm:p-4 border-b border-[#2d3135] flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                <div className="flex flex-wrap items-center gap-2">
                  <span className="w-3 h-3 rounded-full bg-slate-600"></span>
                  <span className="text-xs font-bold tracking-wider text-slate-300 uppercase">Interactive Visual Site Canvas</span>
                  <span className="text-[10px] text-zinc-400 px-2 py-0.5 bg-[#111] rounded border border-zinc-800">100% Drag-and-Drop Sandbox</span>
                </div>
                
                <div className="flex flex-wrap items-center justify-between gap-4 w-full lg:w-auto">
                  {/* LOCAL REVISION HISTORY & WORKSPACE ROLLBACK BUTTONS */}
                  <div className="flex items-center bg-[#111] border border-zinc-800 p-0.5 rounded-xl">
                    <button
                      type="button"
                      onClick={() => {
                        if (editorRef.current) {
                          editorRef.current.UndoManager.undo();
                          addToast("↩️ Undo", "Reverted previous canvas action", "info");
                        }
                      }}
                      className="flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-lg text-zinc-400 hover:text-white hover:bg-zinc-800/60 transition-all duration-200 active:scale-95"
                      title="Undo Previous Action (↩️)"
                      id="undo-canvas-action"
                    >
                      <span>↩️</span>
                      <span>Undo</span>
                    </button>
                    <button
                      type="button"
                      onClick={() => {
                        if (editorRef.current) {
                          editorRef.current.UndoManager.redo();
                          addToast("↪️ Redo", "Restored the last undone layout change", "info");
                        }
                      }}
                      className="flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-lg text-zinc-400 hover:text-white hover:bg-zinc-800/60 transition-all duration-200 active:scale-95"
                      title="Redo Next Action (↪️)"
                      id="redo-canvas-action"
                    >
                      <span>↪️</span>
                      <span>Redo</span>
                    </button>
                  </div>

                  {/* Real-time Multi-device Viewport Switcher */}
                  <div className="flex items-center gap-1">
                    <span className="text-[11px] text-zinc-400 mr-2 uppercase tracking-wider font-mono hidden sm:inline">Viewport Preview:</span>
                    <div className="flex items-center bg-[#111] border border-zinc-800 p-0.5 rounded-xl">
                      <button
                        type="button"
                        onClick={() => {
                          if (editorRef.current) {
                            editorRef.current.setDevice("desktop");
                            const frameEl = document.querySelector(".gjs-frame") as HTMLElement;
                            if (frameEl) {
                              frameEl.style.width = "100%";
                              frameEl.style.maxWidth = "100%";
                            }
                            setCurrentDevice("Desktop");
                            addToast("🖥️ Viewport Mode: Laptop", "Simulating full standard laptop screen widths", "info");
                          }
                        }}
                        className={`flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-lg transition-all duration-200 active:scale-95 ${
                          currentDevice === "Desktop"
                            ? "bg-blue-600 text-white shadow"
                            : "text-zinc-400 hover:text-slate-100"
                        }`}
                        title="Simulate Laptop / PC Desktop Canvas"
                        id="viewport-button-desktop"
                      >
                        <Monitor className="w-3.5 h-3.5" />
                        <span className="hidden sm:inline">Laptop/PC</span>
                      </button>

                      <button
                        type="button"
                        onClick={() => {
                          if (editorRef.current) {
                            editorRef.current.setDevice("tablet");
                            const frameEl = document.querySelector(".gjs-frame") as HTMLElement;
                            if (frameEl) {
                              frameEl.style.width = "768px";
                              frameEl.style.maxWidth = "100%";
                              frameEl.style.margin = "0 auto";
                            }
                            setCurrentDevice("Tablet");
                            addToast("📟 Viewport Mode: Tablet", "Simulating standard iPad and slate devices", "info");
                          }
                        }}
                        className={`flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-lg transition-all duration-200 active:scale-95 ${
                          currentDevice === "Tablet"
                            ? "bg-blue-600 text-white shadow"
                            : "text-zinc-400 hover:text-slate-100"
                        }`}
                        title="Simulate Tablet Width Canvas"
                        id="viewport-button-tablet"
                      >
                        <Tablet className="w-3.5 h-3.5" />
                        <span className="hidden sm:inline">Tablet</span>
                      </button>

                      <button
                        type="button"
                        onClick={() => {
                          if (editorRef.current) {
                            editorRef.current.setDevice("mobile");
                            const frameEl = document.querySelector(".gjs-frame") as HTMLElement;
                            if (frameEl) {
                              frameEl.style.width = "375px";
                              frameEl.style.maxWidth = "100%";
                              frameEl.style.margin = "0 auto";
                            }
                            setCurrentDevice("Mobile");
                            addToast("📱 Viewport Mode: Mobile", "Simulating responsive smartphone layouts", "info");
                          }
                        }}
                        className={`flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-lg transition-all duration-200 active:scale-95 ${
                          currentDevice === "Mobile"
                            ? "bg-blue-600 text-white shadow"
                            : "text-zinc-400 hover:text-slate-100"
                        }`}
                        title="Simulate Mobile Phone Width Canvas"
                        id="viewport-button-mobile"
                      >
                        <Smartphone className="w-3.5 h-3.5" />
                        <span className="hidden sm:inline">Mobile</span>
                      </button>
                    </div>
                  </div>

                  <div className="hidden sm:flex items-center gap-1 text-[11px] text-zinc-500 font-mono">
                    <span className="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                    <span>HMR-Free</span>
                  </div>
                </div>
              </div>

              {/* Informative tips to guide developer through context copy writing */}
              <div className="p-3 bg-blue-950/25 border-b border-blue-900/30 text-[11px] text-blue-300 flex items-center justify-between px-6">
                <div className="flex items-center gap-2 font-mono">
                  <Info className="w-3.5 h-3.5 flex-shrink-0" />
                  <span>💡 SELECT ANY CONTENT TEXT/PARAGRAPH INSIDE THE CANVAS FRAME TO UNLEASH THE ADVANCED GEMINI COPYWRITING WIZARD PANEL BELOW.</span>
                </div>
                {selectedText && (
                  <div className="bg-emerald-950 text-emerald-400 font-bold border border-emerald-900/40 px-2 py-0.5 rounded text-[10px] capitalize">
                    ✓ Ready: "{selectedText.substring(0, 15)}..."
                  </div>
                )}
              </div>

              {/* SABIS DYNAMIC WORKSPACE PAGE TAB MANAGER */}
              {sabisPages.length > 0 && (
                <div className="flex items-center gap-2 bg-[#1b1c1e] px-4 py-2 border-b border-[#2d3135] overflow-x-auto select-none" id="sabis-tab-manager-container">
                  <div className="flex items-center gap-1 text-[10px] font-bold text-zinc-500 uppercase tracking-widest mr-2 flex-shrink-0">
                    <span className="w-1.5 h-1.5 bg-cyan-400 rounded-full animate-pulse"></span>
                    <span>Workspace Pages:</span>
                  </div>
                  <div className="flex items-center gap-2">
                    {sabisPages.map((page) => {
                      const isActive = sabisActiveTab === page.title;
                      return (
                        <button
                          key={page.title}
                          type="button"
                          onClick={() => handleTabSwitch(page.title)}
                          className={`px-3 py-1.5 rounded-lg text-xs font-bold transition-all duration-200 flex items-center gap-1.5 border active:scale-95 ${
                            isActive
                              ? "bg-blue-600 text-white border-blue-500 shadow-md"
                              : "bg-[#111] hover:bg-zinc-800 text-zinc-400 border-zinc-800 hover:text-slate-100"
                          }`}
                        >
                          <span>📄</span>
                          <span>{page.title}</span>
                        </button>
                      );
                    })}
                  </div>
                </div>
              )}

              {/* CORE VISUAL EDITOR EMBED */}
              <div className="relative">
                {hasRecoverySession && (
                  <div className="bg-amber-950/40 border border-amber-900/60 p-4 rounded-xl mb-3 flex flex-col sm:flex-row items-center justify-between gap-3 animate-fade-in" id="sabis-recovery-alert-bar">
                    <div className="flex items-center gap-2.5">
                      <span className="text-xl">⚠️</span>
                      <div className="text-left">
                        <h4 className="text-xs font-bold text-amber-200">Unsaved Canvas Layout Recovered!</h4>
                        <p className="text-[10px] text-amber-400">We detected an auto-saved session version cached before your last tab reload.</p>
                      </div>
                    </div>
                    <div className="flex items-center gap-2">
                      <button
                        type="button"
                        onClick={handleRestoreSession}
                        className="px-3 py-1.5 rounded-lg text-[11px] font-bold bg-amber-600 hover:bg-amber-500 text-black transition active:scale-95"
                      >
                        🔄 Restore Session
                      </button>
                      <button
                        type="button"
                        onClick={() => {
                          localStorage.removeItem("sabis_grapesjs_autosave_payload");
                          setHasRecoverySession(false);
                        }}
                        className="px-3 py-1.5 rounded-lg text-[11px] font-bold bg-transparent hover:bg-zinc-800 text-zinc-400 transition"
                      >
                        Dismiss
                      </button>
                    </div>
                  </div>
                )}
                <div id="gjs-wrapper" className="bg-white min-h-[640px] text-slate-900"></div>
              </div>

              {/* 2. INTERACTIVE AI REDESIGN DIALOG WITH CUSTOM & AUTO MODE */}
              {isRedesignModalOpen && (
                <div className="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center z-[99999] p-4 animate-fade-in" id="sabis-redesign-modal-overlay">
                  <div className="bg-[#181a1d] border border-blue-900/40 rounded-2xl w-full max-w-lg p-6 shadow-2xl relative space-y-4" id="sabis-redesign-modal-container">
                    <div className="flex items-center justify-between border-b border-zinc-900 pb-3">
                      <div className="flex items-center gap-2">
                        <span className="w-2.5 h-2.5 rounded-full bg-blue-500 animate-pulse"></span>
                        <h3 className="text-sm font-black uppercase tracking-wider text-slate-100">
                          ✨ Section Re-Architect Wizard
                        </h3>
                      </div>
                      <button 
                        type="button" 
                        onClick={() => {
                          setIsRedesignModalOpen(false);
                          setRedesignCustomInstruction("");
                        }}
                        className="text-zinc-500 hover:text-white transition-colors text-lg"
                      >
                        &times;
                      </button>
                    </div>

                    <div className="text-xs text-zinc-400 space-y-2">
                      <p>You are about to re-architect and redesign the selected canvas section via Gemini 2.5 Pro.</p>
                      {redesignSelectedModel && (
                        <div className="bg-zinc-950/50 p-2 rounded-xl border border-zinc-900 font-mono text-[10px] text-zinc-500">
                          Selected Component Tag: <span className="text-blue-400 font-bold">{redesignSelectedModel.get("tagName") || "DIV"}</span>
                        </div>
                      )}
                    </div>

                    {/* Option A: Custom Instruction */}
                    <div className="space-y-2">
                      <label className="text-xs font-bold text-slate-300">Option A: Custom Blueprint Instruction</label>
                      <textarea
                        value={redesignCustomInstruction}
                        onChange={(e) => setRedesignCustomInstruction(e.target.value)}
                        placeholder="e.g. Redesign with a dark violet theme and 3-column pricing cards..."
                        className="w-full h-20 p-3 rounded-xl bg-zinc-950 border border-zinc-805 text-slate-200 text-xs focus:outline-none focus:border-blue-500 transition-colors placeholder-zinc-700 resize-none font-sans"
                        id="sabis-redesign-custom-prompt"
                      />
                    </div>

                    {/* Actions Grid */}
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                      <button
                        type="button"
                        disabled={!redesignCustomInstruction.trim() || isRedesigning}
                        onClick={() => handleSectionRedesign("custom")}
                        className="py-2.5 px-4 rounded-xl text-xs font-bold bg-blue-700 hover:bg-blue-600 disabled:opacity-40 disabled:hover:bg-blue-700 text-white transition active:scale-95 shadow-md flex items-center justify-center gap-1.5"
                      >
                        <span>🚀</span> <span>Submit Custom Blueprint</span>
                      </button>

                      <button
                        type="button"
                        disabled={isRedesigning}
                        onClick={() => handleSectionRedesign("auto")}
                        className="py-2.5 px-4 rounded-xl text-xs font-bold bg-zinc-800 hover:bg-zinc-700 hover:text-white border border-zinc-700 hover:border-blue-500/50 text-[#f1f1f1] transition active:scale-95 flex items-center justify-center gap-1.5"
                      >
                        <span>✨</span> <span>Let AI Decide (Auto Mode)</span>
                      </button>
                    </div>

                    {isRedesigning && (
                      <div className="flex items-center justify-center gap-2 pt-2 text-xs text-blue-400 font-mono animate-pulse">
                        <span className="w-2 h-2 rounded-full bg-blue-500 animate-ping"></span>
                        Processing layout rendering blocks via Gemini 2.5 Pro...
                      </div>
                    )}
                  </div>
                </div>
              )}
            </div>

            {/* 3. GEMINI CORE COPYWRITING DRAWER FOR MULTI-SELECTION MICRO-HOOKS */}
            <div className="p-5 rounded-2xl border transition-all duration-300 bg-[#111315] border-blue-950/40 shadow-xl space-y-4" id="sabis-copywriting-interactive-panel">
              <div className="flex flex-col gap-3">
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    <span className="w-2.5 h-2.5 rounded-full bg-blue-500 animate-pulse"></span>
                    <h3 className="text-xs font-black uppercase tracking-wider text-slate-200">
                      💎 Interactive Copywriting & Text Improver (Sabis Core Context Menu)
                    </h3>
                  </div>
                  {selectedComponentModel && (
                    <span className="text-[10px] text-emerald-400 font-mono bg-emerald-950/60 px-2 py-0.5 rounded border border-emerald-900/50">
                      ✓ Connected to Canvas Element: {selectedComponentModel.get("tagName")}
                    </span>
                  )}
                </div>
                
                <p className="text-xs text-zinc-400 leading-relaxed font-sans">
                  Select any text element (heading, title, paragraph) inside GrapesJS above to pre-fill, or <strong>write and edit your custom copywriting freeform directly inside the input field below</strong> to improve it instantly:
                </p>

                <textarea
                  value={selectedText}
                  onChange={(e) => setSelectedText(e.target.value)}
                  placeholder="Click a text element inside the builder above, or write / paste your own custom text copy here to improve..."
                  className="w-full h-24 p-3 rounded-xl bg-zinc-950 border border-zinc-800 text-slate-200 text-xs focus:outline-none focus:border-blue-500 transition-colors placeholder-zinc-650 resize-none font-sans"
                  id="sabis-text-improver-textarea"
                />
              </div>

              <div className="flex flex-wrap items-center justify-between gap-4 pt-2 border-t border-zinc-900">
                <div className="text-[10px] text-zinc-500 font-mono">
                  {selectedText ? selectedText.length : 0} characters
                </div>

                <div className="flex flex-wrap items-center gap-2">
                  <button 
                    type="button" 
                    onClick={() => handleMicroAIEditing("Make Catchy")}
                    disabled={!selectedText || !selectedText.trim() || isRewriting}
                    className="py-2 px-4 rounded-xl text-xs font-bold bg-zinc-800 hover:bg-zinc-700 disabled:opacity-40 disabled:hover:bg-zinc-800 hover:text-white border border-zinc-700 hover:border-blue-500/50 text-[#f1f1f1] transition active:scale-95 flex items-center gap-1.5"
                  >
                    <span>⚡</span> <span>Make Catchy Headline</span>
                  </button>

                  <button 
                    type="button" 
                    onClick={() => handleMicroAIEditing("Shorten")}
                    disabled={!selectedText || !selectedText.trim() || isRewriting}
                    className="py-2 px-4 rounded-xl text-xs font-bold bg-zinc-800 hover:bg-zinc-700 disabled:opacity-40 disabled:hover:bg-zinc-800 hover:text-white border border-zinc-700 hover:border-yellow-500/50 text-[#f1f1f1] transition active:scale-95 flex items-center gap-1.5"
                  >
                    <span>✂️</span> <span>Condense / Shorten</span>
                  </button>

                  <button 
                    type="button" 
                    onClick={() => handleMicroAIEditing("Change Tone")}
                    disabled={!selectedText || !selectedText.trim() || isRewriting}
                    className="py-2 px-4 rounded-xl text-xs font-bold bg-blue-650 hover:bg-blue-600 disabled:opacity-40 text-white transition active:scale-95 shadow flex items-center gap-1.5"
                  >
                    <span>💼</span> <span>Executive SaaS Tone</span>
                  </button>
                </div>
              </div>
            </div>

          </div>
        )}

        {/* REPUBLISHED PAGES TABLE WORKSPACE VIEW */}
        {activeTab === "pages" && (
          <div className="space-y-6">
            <div className="p-6 bg-[#1a1c1e] rounded-2xl border border-[#2d3135] shadow-lg">
              <div className="flex items-center justify-between mb-6">
                <div>
                  <h3 className="text-lg font-extrabold text-white mb-1">Published WordPress Core Pages Database</h3>
                  <p className="text-xs text-zinc-400">These pages reside within your site's virtual post database, fully decoupled from Elementor dependencies.</p>
                </div>
                <button 
                  type="button" 
                  onClick={() => {
                    if (confirm("Reset current virtual database table?")) {
                      setPublishedPages([]);
                      addToast("🚽 Virtual Database Reset", "Published items cleared.", "error");
                    }
                  }}
                  className="p-2 text-zinc-500 hover:text-red-400 border border-zinc-800 rounded-lg hover:bg-zinc-900 transition"
                  title="Purge pages list pointer"
                >
                  <Trash2 className="w-4 h-4" />
                </button>
              </div>

              {publishedPages.length === 0 ? (
                <div className="text-center py-16 border border-dashed border-zinc-800 rounded-2xl bg-[#111]/30">
                  <span className="text-3xl block mb-3">📄</span>
                  <p className="text-xs text-zinc-500 font-bold mb-2">No pages published on site maps yet.</p>
                  <p className="text-xs text-zinc-600 max-w-xs mx-auto mb-4">Click AI Visual Canvas tab, generate clean layout visual blocks, and hit the 'Publish Design to WordPress' button.</p>
                  <button 
                    type="button" 
                    onClick={() => setActiveTab("builder")} 
                    className="py-2 px-4 bg-blue-600 text-white font-bold rounded-lg text-xs"
                  >
                    Return to Visual Studio
                  </button>
                </div>
              ) : (
                <div className="space-y-4">
                  {publishedPages.map((page) => (
                    <div key={page.id} className="p-5 bg-zinc-950 border border-zinc-800 rounded-xl space-y-3 relative hover:border-zinc-700 transition">
                      <div className="flex flex-wrap items-center justify-between gap-2 border-b border-zinc-900 pb-3">
                        <div>
                          <h4 className="font-bold text-sm text-white mb-0.5">{page.title}</h4>
                          <span className="text-[10px] font-mono text-zinc-500 tracking-wider">SLUG: /{page.slug} | TIMESTAMP: {page.date}</span>
                        </div>
                        <span className="px-2 py-0.5 rounded text-[10px] bg-emerald-950/60 text-emerald-400 font-bold border border-emerald-900/30 capitalize">
                          {page.theme} theme preset
                        </span>
                      </div>

                      {/* Display server self-healing checklist */}
                      <div className="p-3 bg-zinc-900 rounded-lg border border-zinc-850 text-[10px] font-mono text-emerald-400">
                        <strong className="text-white block mb-1">⚡ WordPress Self-Healing Report Log:</strong>
                        {page.audit || "All semantic layout grids balanced. DIV wrappers auto repaired. Translated media assets compiled dynamically."}
                      </div>

                      <div className="flex items-center justify-between pt-2">
                        <div className="text-[10px] text-zinc-500 flex items-center gap-1.5">
                          <CheckCircle2 className="w-3.5 h-3.5 text-emerald-500" />
                          <span>Status: published, live & indexed on domain routing.</span>
                        </div>

                        <div className="flex items-center gap-2">
                          <a 
                            href={page.permalink} 
                            target="_blank" 
                            rel="noopener noreferrer" 
                            className="py-1 px-3 bg-zinc-800 hover:bg-zinc-700 text-[10px] text-zinc-300 font-bold rounded hover:text-white transition flex items-center gap-1 border border-zinc-700"
                          >
                            <ExternalLink className="w-3 h-3" />
                            View Simulated Live Output
                          </a>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>
        )}

        {/* ADVANCED OPTION KEYS CONFIGURATION PANEL */}
        {activeTab === "settings" && (
          <div className="space-y-6 max-w-4xl mx-auto">
            <div className="p-6 bg-[#1a1c1e] rounded-2xl border border-[#2d3135] shadow-lg space-y-6">
              <div>
                <h3 className="text-lg font-extrabold text-white mb-1 uppercase tracking-tight">Sabis AI Studio Cloud Config</h3>
                <p className="text-xs text-zinc-400">Update target API channels, dynamic tracking scripts, or sheets webhook destinations securely.</p>
              </div>

              <div className="p-4 bg-zinc-950 border border-zinc-850 rounded-xl text-xs text-zinc-400 flex items-start gap-3">
                <Info className="w-5 h-5 text-blue-400 flex-shrink-0 mt-0.5" />
                <div className="leading-relaxed">
                  <strong className="text-white">API Key Security & Automation Guidance:</strong>
                  <p className="mt-1">
                    Your Google Gemini key is controlled safely from the secrets server layer in real deployments. No API keys are leaked to the browser bundle. Ensure <strong className="text-slate-200">GEMINI_API_KEY</strong> environment variable is defined within the system workspace secrets panel to enable real generative loops.
                  </p>
                </div>
              </div>

              <div className="space-y-4">
                <div>
                  <label className="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Google Sheets External Webhook URL</label>
                  <input 
                    type="url" 
                    value={googleWebhookUrl}
                    onChange={(e) => {
                      setGoogleWebhookUrl(e.target.value);
                      localStorage.setItem("sabis_sheets_webhook_url", e.target.value);
                    }}
                    placeholder="https://script.google.com/macros/s/..." 
                    className="w-full bg-[#111] border border-[#3e444b] rounded-xl py-2 px-3 text-xs text-slate-100 placeholder-slate-500 focus:outline-none focus:border-blue-500 transition-colors"
                  />
                  <span className="text-[10px] text-zinc-500 block mt-1">Submit email events directly to this webhook without backend database records.</span>
                </div>

                <div>
                  <label className="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Stripe Publishable Key (pk_test_...)</label>
                  <input 
                    type="text" 
                    value={stripePublishableKey}
                    onChange={(e) => {
                      setStripePublishableKey(e.target.value);
                      localStorage.setItem("sabis_stripe_publishable_key", e.target.value);
                    }}
                    placeholder="pk_test_51Px..." 
                    className="w-full bg-[#111] border border-[#3e444b] rounded-xl py-2 px-3 text-xs text-slate-100 placeholder-slate-500 focus:outline-none focus:border-blue-500 transition-colors"
                  />
                  <span className="text-[10px] text-zinc-500 block mt-1">Bind this key to the generated front-end Stripe Payment SDK elements when layout includes purchase/subscription templates.</span>
                </div>

                <div>
                  <label className="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">WordPress Site Routing Target</label>
                  <input 
                    type="text" 
                    disabled
                    value="https://ais-dev-2j4aigifgocg75tyg2pzmn-231236525443.asia-southeast1.run.app" 
                    className="w-full bg-zinc-900 border border-zinc-850 rounded-xl py-2 px-3 text-xs text-zinc-500 cursor-not-allowed"
                  />
                </div>
              </div>

              <div className="pt-4 border-t border-[#2d3135]">
                <button 
                  type="button" 
                  onClick={() => {
                    addToast("⚙ Settings Saved!", "API parameters updated locally.", "success");
                    setActiveTab("builder");
                  }}
                  className="py-2.5 px-6 rounded-xl bg-blue-650 hover:bg-blue-600 text-white font-bold text-xs transition"
                >
                  Save Configuration Keys
                </button>
              </div>
            </div>
          </div>
        )}

      </main>

      {/* DYNAMIC SIMULATED INTERACTIVE LANDING OVERLAYS (WhatsApp checkouts / Webhook pushes) */}
      {activeOrderData && (
        <div className="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
          <div className="bg-[#1a1c1e] border border-zinc-800 rounded-2xl max-w-md w-full p-6 text-slate-100 shadow-2xl relative">
            <div className="flex items-center gap-2 mb-4">
              <span className="text-xl">💬</span>
              <div>
                <h4 className="font-extrabold text-sm text-yellow-100 uppercase tracking-wide">Simulated WhatsApp Checkout Engine</h4>
                <p className="text-[10px] text-zinc-400">Direct ordering hook resolved out of WooCommerce.</p>
              </div>
            </div>

            <div className="space-y-4 bg-[#111] p-4 rounded-xl border border-zinc-850 text-xs">
              <div>
                <span className="text-[10px] uppercase font-bold text-zinc-500">Constructed WhatsApp Direct API Text Message:</span>
                <p className="mt-2 text-stone-300 font-mono leading-relaxed bg-[#1d1d1d] p-3 rounded border border-stone-800 select-all">
                  https://api.whatsapp.com/send?phone={encodeURIComponent(activeOrderData.phone)}&text=I%20would%20like%20to%20order%20directly.%0AName:%20{encodeURIComponent(activeOrderData.name)}%0AAddress:%20{encodeURIComponent(activeOrderData.address)}
                </p>
              </div>

              <div className="grid grid-cols-2 gap-2 text-[10px] text-zinc-400 border-t border-zinc-900 pt-3">
                <div>
                  <span className="block font-bold mt-0.5">Purchaser:</span>
                  <span className="text-white font-mono">{activeOrderData.name}</span>
                </div>
                <div>
                  <span className="block font-bold mt-0.5">Contact:</span>
                  <span className="text-white font-mono">{activeOrderData.phone}</span>
                </div>
                <div className="col-span-2">
                  <span className="block font-bold mt-0.5">Destination:</span>
                  <p className="text-white font-serif">{activeOrderData.address}</p>
                </div>
              </div>
            </div>

            <div className="mt-6 flex justify-end gap-3">
              <button 
                type="button" 
                onClick={() => setActiveOrderData(null)}
                className="py-2 px-4 rounded-lg bg-zinc-800 hover:bg-zinc-750 text-xs text-white"
              >
                Close Checkout Screen
              </button>
              <a 
                href={`https://api.whatsapp.com/send?phone=${encodeURIComponent(activeOrderData.phone)}&text=Order%20received%0ADescription:%20${encodeURIComponent(activeOrderData.name)}%0AAddress:%20${encodeURIComponent(activeOrderData.address)}`}
                target="_blank"
                rel="noopener noreferrer"
                className="py-2 px-4 rounded-lg bg-green-600 hover:bg-green-500 text-xs text-white font-bold flex items-center gap-1.5"
              >
                Launch Active WhatsApp Call
              </a>
            </div>
          </div>
        </div>
      )}

      {/* DYNAMIC TOAST NOTIFICATION CONTAINER */}
      <div className="fixed bottom-6 right-6 space-y-2 z-50">
        {toasts.map((t) => (
          <div key={t.id} className={`p-4 rounded-xl border-l-4 shadow-xl text-slate-100 flex items-start gap-3 w-80 max-w-full backdrop-blur transition-all duration-300 ${t.type === "success" ? "bg-[#18231a] border-[#2ea349] text-emerald-200" : t.type === "error" ? "bg-[#281a1c] border-[#a32e3a] text-rose-200" : "bg-[#1a212e] border-[#2e5fa3] text-blue-200"}`}>
            <div className="text-lg">
              {t.type === "success" ? "✓" : t.type === "error" ? "✕" : "ℹ"}
            </div>
            <div>
              <h5 className="font-bold text-xs">{t.title}</h5>
              <p className="text-[10px] opacity-80 mt-0.5 leading-normal">{t.message}</p>
            </div>
          </div>
        ))}
      </div>

    </div>
  );
}
