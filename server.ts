import express from "express";
import path from "path";
import dotenv from "dotenv";
import { createServer as createViteServer } from "vite";
import { GoogleGenAI } from "@google/genai";

dotenv.config();

const app = express();
app.use(express.json({ limit: "10mb" }));

const PORT = 3000;

// Lazy initialization function for Google Gen AI
let aiClient: GoogleGenAI | null = null;
function getAiClient(): GoogleGenAI {
  if (!aiClient) {
    const key = process.env.GEMINI_API_KEY;
    if (!key) {
      throw new Error("GEMINI_API_KEY environment variable is required in secrets");
    }
    aiClient = new GoogleGenAI({
      apiKey: key,
      httpOptions: {
        headers: {
          "User-Agent": "aistudio-build",
        },
      },
    });
  }
  return aiClient;
}

function applyIsolateWrapper(htmlStr: string): string {
  if (!htmlStr || typeof htmlStr !== "string" || htmlStr.trim() === "") return htmlStr;
  const trimmed = htmlStr.trim();
  const idStr = "sabis-iso-" + Math.random().toString(36).substring(2, 9);
  
  if (trimmed.includes("data-sabis-isolated=")) {
    return htmlStr;
  }

  return `<div class="sabis-layout-isolated-wrapper relative w-full" data-sabis-isolated="true" id="${idStr}">${htmlStr}</div>`;
}

// ----------------------------------------------------
// 1. API: Layout Generation via Gemini & Groq Hybrid Chain
// ----------------------------------------------------
app.post("/api/generate_page", async (req, res) => {
  try {
    const { prompt, themePreset, referenceImage, pageCount, templates, stripeKey, customThemePresetDescription } = req.body;
    if (!prompt) {
      return res.status(400).json({ error: "Prompt is required" });
    }

    let enhancedInstruction = prompt;

    // Check if Groq API key is present for Prompt Enhancement
    const groqKey = process.env.GROQ_API_KEY;
    if (groqKey) {
      try {
        const groqRes = await fetch("https://api.groq.com/openai/v1/chat/completions", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "Authorization": `Bearer ${groqKey}`
          },
          body: JSON.stringify({
            model: "llama-3.3-70b-versatile",
            messages: [
              {
                role: "system",
                content: "You are an Expert Prompt Engineer. Expand the simple user words into a highly descriptive, luxury web UI layout blueprint instruction. Describe content structure, premium layout aesthetics, grid organization, and visual cues. Output ONLY the expanded blueprint instruction, no conversation, no markdown formatting."
              },
              {
                role: "user",
                content: prompt
              }
            ],
            temperature: 0.7
          })
        });

        if (groqRes.ok) {
          const groqData: any = await groqRes.json();
          if (groqData.choices && groqData.choices[0] && groqData.choices[0].message) {
            enhancedInstruction = groqData.choices[0].message.content.trim();
          }
        }
      } catch (e) {
        console.error("Failed to enhance prompt via Groq, falling back to original", e);
      }
    }

    const ai = getAiClient();

    const paymentPromptAddon = stripeKey 
      ? `CRITICAL NO-CODE PAYMENT ACTION: We have an active Stripe Publishable Key: '${stripeKey}'. If checkout forms, pricing visual blocks, shopping carts, or digital payment features are requested or suited, you MUST automatically output a highly secure, functional, fully customized visual script layout utilizing official SDK libraries 'https://js.stripe.com/v3/' to render smart credit card payment fields dynamically. Setup Stripe, mount elements, and style them perfectly using Tailwind. For example, include a credit card input and a secure Checkout button to handle transaction submissions locally without WooCommerce.`
      : `PAYMENT INTEGRATION WARNING: If payment fields are needed, render direct payment UI placeholders and mention Stripe publishable key setup in settings.`;

    let isMultiPage = (pageCount && pageCount > 1) || (templates && templates.length > 0);

    let systemInstruction = `
      You are an elite, modern Web Designer and No-Code Tailwind CSS Master.
      Style everything beautifully with rich backgrounds, grids, flex containers, elegant spacing, card transitions, responsive typography, and crisp elements. Use standard Unsplash URLs related to the context for images, ensure <img> tags have referrerPolicy="no-referrer" and alt tags. Use the chosen accent theme preset: [${themePreset || 'Minimal Light'}].
      Preset theme profiles available:
      - 'Minimal Light': sleek off-whites (bg-zinc-50), subtle gray borders (border-zinc-200), pure black text, sophisticated light charcoal cards, rich spacing.
      - 'Cyberpunk Dark': neon accents (pink/cyan/indigo), dark slate/purple backgrounds (bg-slate-950), glowing borders and cyber neon design elements.
      - 'Royal Gold': emerald/dark-blue/gold accents, opulent visual elegance (bg-slate-900), amber/gold text accents, premium royal borders.
      - 'Sunset Orange & Charcoal': dark charcoal cards, vibrant sunset gradients, neon orange and gold highlights, warm aesthetic borders.
      - 'Forest Emerald & Sage': earthy calming green accents, deep emerald and organic sage backgrounds, biological/herbal visual cues.
      - 'Deep Ocean Blues': premium corporate blues, elegant deep-navy cards, rich teal highlights, sleek professional shadows.
      - 'Dracula Vampire': retro purples, deep blood-red accents, neon magenta text elements, dark high-contrast cyber-gothic palette.
      - 'Custom Accent': custom user style configuration described as: [ ${customThemePresetDescription || 'No description provided'} ]. You MUST base the entire web visual style, color palette, gradients, and layout aura on this exact customized style description.
      ${paymentPromptAddon}
    `;

    if (isMultiPage) {
      systemInstruction += `
        CRITICAL MULTI-PAGE INSTRUCTION:
        You MUST generate a valid JSON array of page objects. Each object represents a complete page structure styled recursively with inline Tailwind CSS classes.
        The needed pages are defined as: ${templates ? templates.join(", ") : "Home, About Us, Services, Contact"}.
        Output ONLY the raw JSON structure, format should be exactly like:
        [
          {
            "title": "Home",
            "html": "<section class='...'>Home section markup and premium visual components...</section>"
          },
          {
            "title": "About Us",
            "html": "<section class='...'>About us details, premium grid and features...</section>"
          }
        ]
        Do NOT wrap in triple-backticks \`\`\`json. Return pure JSON.
      `;
    } else {
      systemInstruction += `
        CRITICAL SINGLE-PAGE INSTRUCTION:
        Output ONLY clean, valid, standalone semantic HTML tags. Do NOT wrap in <html>, <head>, or <body>. Do NOT include markdown blocks (\`\`\`html) or explanation text.
      `;
    }

    let contentsParts: any[] = [];
    if (referenceImage) {
      const match = referenceImage.match(/^data:(image\/[a-zA-Z+]+);base64,(.+)$/);
      if (match) {
        const mimeType = match[1];
        const base64Data = match[2];
        contentsParts.push({
          inlineData: {
            mimeType: mimeType,
            data: base64Data
          }
        });
      }
    }

    let themeStyleName = themePreset || "Minimal Light";
    let userPromptText = `Design a gorgeous web page for: "${enhancedInstruction}". Match the theme style: ${themeStyleName}.`;
    if (themePreset === "Custom Accent" && customThemePresetDescription) {
      userPromptText += ` Use the custom theme details: "${customThemePresetDescription}".`;
    }
    if (referenceImage) {
      userPromptText += ` Carefully analyze the attached wireframe/screenshot reference image and replicate its structural layout, key containers, proportions, font sizes/alignments, and aesthetics beautifully.`;
    }
    if (isMultiPage) {
      userPromptText += ` Please compile all requested pages under pages list in valid JSON format now.`;
    }
    contentsParts.push({ text: userPromptText });

    const contents = contentsParts.length === 1 ? contentsParts[0].text : { parts: contentsParts };

    const config: any = {
      systemInstruction: systemInstruction,
      temperature: 0.7,
    };
    if (isMultiPage) {
      config.responseMimeType = "application/json";
    }

    const response = await ai.models.generateContent({
      model: "gemini-3.5-flash",
      contents: contents,
      config: config
    });

    let rawOutput = response.text || "";
    // Clean up markdown ticks if Gemini outputted any anyway
    rawOutput = rawOutput.replace(/```json/gi, "").replace(/```html/gi, "").replace(/```/gi, "").trim();

    if (isMultiPage) {
      try {
        const parsed = JSON.parse(rawOutput);
        const pages = Array.isArray(parsed) ? parsed : (parsed.pages || [ { title: "Home", html: rawOutput } ]);
        const mappedPages = pages.map((p: any) => ({
          ...p,
          html: applyIsolateWrapper(p.html || "")
        }));
        return res.json({ pages: mappedPages });
      } catch (err) {
        console.error("Failed to parse multipage output from Gemini, returning fallback", err);
        return res.json({
          pages: [
            { title: "Home", html: applyIsolateWrapper(rawOutput) }
          ]
        });
      }
    } else {
      return res.json({ html: applyIsolateWrapper(rawOutput) });
    }
  } catch (error: any) {
    console.error("Gemini Generation Error:", error);
    return res.status(500).json({ error: error.message || "Failed to generate layout via Gemini" });
  }
});

// ----------------------------------------------------
// 2. API: Dynamic Copywriting (Bypassing Gemini to use Fast Groq Llama 3 when available)
// ----------------------------------------------------
app.post("/api/copywrite", async (req, res) => {
  try {
    const { text, action } = req.body;
    if (!text || !action) {
      return res.status(400).json({ error: "Text and Action are required" });
    }

    const groqKey = process.env.GROQ_API_KEY;
    if (groqKey) {
      try {
        const response = await fetch("https://api.groq.com/openai/v1/chat/completions", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "Authorization": `Bearer ${groqKey}`
          },
          body: JSON.stringify({
            model: "llama-3.3-70b-versatile",
            messages: [
              {
                role: "system",
                content: `You are a professional SaaS copywriter. Transform the given text with action: ${action}. Your response MUST contain only the rewritten string, without quotes or explanations.`
              },
              {
                role: "user",
                content: `Process text: "${text}"`
              }
            ],
            temperature: 0.8
          })
        });

        if (response.ok) {
          const data: any = await response.json();
          if (data.choices && data.choices[0] && data.choices[0].message) {
            let answer = data.choices[0].message.content.trim();
            answer = answer.replace(/^["\']|["\']$/g, ""); // strip surrounding quotes
            return res.json({ text: answer });
          }
        } else {
          throw new Error(`Groq API returned error status: ${response.status}`);
        }
      } catch (e: any) {
        console.warn("⚠️ FAILSAFE AUTOMATIC MODEL FALLBACK IN RUNTIME [Copywrite]: Groq endpoint was unavailable, shifting payloads back to primary Google Gemini model.", e.message);
      }
    }

    const ai = getAiClient();

    const instruction = `
      You are an award-winning UI/UX SaaS copywriter.
      Rewrite the text based on the command: "${action}".
      Command options:
      - "Make Catchy": Transform the text into a click-worthy, modern headline/text with a high converting emotional trigger.
      - "Shorten": Condense the phrasing to be punchy, brief, and mobile-friendly without losing core value.
      - "Change Tone": Adopt a highly professional, authoritative executive SaaS tone or rich playful tone.
      
      CRITICAL: Output ONLY the revised text string. No quotes, no markdown, no conversational prefaces. Keep the length proportional.
    `;

    const response = await ai.models.generateContent({
      model: "gemini-3.5-flash",
      contents: `Text to modify: "${text}"`,
      config: {
        systemInstruction: instruction,
        temperature: 0.8,
      },
    });

    const revisedText = (response.text || "").trim();
    return res.json({ text: revisedText });
  } catch (error: any) {
    console.error("Gemini Copywriting Error:", error);
    return res.status(500).json({ error: error.message || "Failed to modify text" });
  }
});

// ----------------------------------------------------
// 3. API: Multilingual Translation (English <-> Urdu via Fast Groq Llama 3 when available)
// ----------------------------------------------------
app.post("/api/translate", async (req, res) => {
  try {
    const { text, targetLang } = req.body; // 'urdu' or 'en'
    if (!text) {
      return res.status(400).json({ error: "Text is required" });
    }

    const groqKey = process.env.GROQ_API_KEY;
    if (groqKey) {
      try {
        const systemInstruction = targetLang === "urdu" 
          ? "You are an elite bilingual localized text processing pipeline. Translate the provided HTML structure from English elements to elegant, highly modern marketing Urdu. " +
            "CRITICAL SPECIFICATION: Enforce a strict character isolation and separation boundary. You MUST ONLY swap out human-readable text contents residing inside leaf text nodes (between tag brackets like >TEXT<) or form placeholder/alt attributes. " +
            "You are STRICTLY FORBIDDEN from modifying, truncating, altering, or omitting any HTML tags, CSS rules, class names (especially Tailwind CSS classes), HTML attributes (except for placeholders and alt texts), inline scripts, or layout boundaries. " +
            "Return the complete, perfectly valid, and syntactically identical HTML layout code with absolutely no markdown wrapping, no conversational prefix/suffix, and no structural alterations."
          : "Translate Urdu texts back to English in the structures. Enforce strict character-level isolation boundaries. Leave all code parameters and HTML structures intact.";

        const response = await fetch("https://api.groq.com/openai/v1/chat/completions", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "Authorization": `Bearer ${groqKey}`
          },
          body: JSON.stringify({
            model: "llama-3.3-70b-versatile",
            messages: [
              {
                role: "system",
                content: systemInstruction
              },
              {
                role: "user",
                content: text
              }
            ],
            temperature: 0.3
          })
        });

        if (response.ok) {
          const data: any = await response.json();
          if (data.choices && data.choices[0] && data.choices[0].message) {
            let answer = data.choices[0].message.content.trim();
            // clean markdown fences if any (Failsafe Cleaning Nets)
            answer = answer.replace(/```html/gi, "").replace(/```/gi, "").trim();
            return res.json({ text: answer });
          }
        } else {
          throw new Error(`Groq API returned error status: ${response.status}`);
        }
      } catch (e: any) {
        console.warn("⚠️ FAILSAFE AUTOMATIC MODEL FALLBACK IN RUNTIME [Translate]: Groq endpoint was unavailable, shifting payloads back to primary Google Gemini model.", e.message);
      }
    }

    const ai = getAiClient();
    const systemInstruction = targetLang === "urdu" 
      ? "You are an elite bilingual localized text processing pipeline. Translate the provided HTML structure from English elements to elegant, highly modern marketing Urdu. " +
        "CRITICAL SPECIFICATION: Enforce a strict character isolation and separation boundary. You MUST ONLY swap out human-readable text contents residing inside leaf text nodes (between tag brackets like >TEXT<) or form placeholder/alt attributes. " +
        "You are STRICTLY FORBIDDEN from modifying, truncating, altering, or omitting any HTML tags, CSS rules, class names (especially Tailwind CSS classes), HTML attributes (except for placeholders and alt texts), inline scripts, or layout boundaries. " +
        "Return the complete, perfectly valid, and syntactically identical HTML layout code with absolutely no markdown wrapping, no conversational prefix/suffix, and no structural alterations."
      : "Translate Urdu texts back to English in the structures. Enforce strict character-level isolation boundaries. Leave all code parameters and HTML structures intact.";

    const response = await ai.models.generateContent({
      model: "gemini-3.5-flash",
      contents: `Translate this: "${text}"`,
      config: {
        systemInstruction: systemInstruction,
        temperature: 0.3,
      },
    });

    let translatedText = (response.text || "").trim();
    // clean markdown fences if any (Failsafe Cleaning Nets)
    translatedText = translatedText.replace(/```html/gi, "").replace(/```/gi, "").trim();
    return res.json({ text: translatedText });
  } catch (error: any) {
    console.error("Gemini Translation Error:", error);
    return res.status(500).json({ error: error.message || "Failed to translate copy" });
  }
});

// ----------------------------------------------------
// 3b. API: Section Redesign via Gemini 2.5 Pro (Simulated)
// ----------------------------------------------------
app.post("/api/regenerate_section", async (req, res) => {
  try {
    const { sectionHtml, instruction, themePreset } = req.body;
    if (!sectionHtml || !instruction) {
      return res.status(400).json({ error: "sectionHtml and instruction are required" });
    }

    const ai = getAiClient();
    const systemInstruction = `
      You are an elite, modern Web Designer and No-Code Tailwind CSS Master.
      You are given an existing HTML segment and a layout modification instruction.
      You must redesign/modify ONLY that segment. Do NOT change its wrapping or outer element tag unless requested.
      Output ONLY clean, valid standalone HTML with inline Tailwind classes. Ensure the design follows the preset requested [${themePreset || 'Minimal Light'}].
      Do NOT return markdown blocks or any text explanations.
    `;

    const response = await ai.models.generateContent({
      model: "gemini-3.5-flash",
      contents: `Existing HTML:\n${sectionHtml}\n\nModification Instruction: ${instruction}`,
      config: {
        systemInstruction: systemInstruction,
        temperature: 0.7,
      },
    });

    let html = response.text || "";
    // Clean up markdown ticks if Gemini outputted any (Failsafe Cleaning Nets)
    html = html.replace(/```html/gi, "").replace(/```/gi, "").trim();
    html = applyIsolateWrapper(html);

    return res.json({ success: true, html });
  } catch (error: any) {
    console.error("Section Redesign Error:", error);
    return res.status(500).json({ error: error.message || "Failed to redesign section" });
  }
});

// ----------------------------------------------------
// 4. API: Publishing Simulation and Server-Side HTML Healing
// ----------------------------------------------------
function optimizeAndMinifyHtml(html: string, promptHint: string = ""): string {
  // 1. Strip out temporary inline HTML/comment nodes
  let cleaned = html.replace(/<!--([\s\S]*?)-->/g, "");

  // 2. Strip excessive whitespace characters but preserve correct tag formatting
  cleaned = cleaned.replace(/\s+/g, " ");
  cleaned = cleaned.replace(/>\s+</g, "><");
  cleaned = cleaned.replace(/ +/g, " ");
  cleaned = cleaned.replace(/\s*=\s*/g, "=");

  // 3. Process image components with empty or placeholder source targets based on category
  let category = "minimalist";
  if (promptHint) {
    const promptLower = promptHint.toLowerCase();
    if (promptLower.includes("coffee") || promptLower.includes("cafe")) {
      category = "coffee";
    } else if (promptLower.includes("tech") || promptLower.includes("saas") || promptLower.includes("software") || promptLower.includes("developer")) {
      category = "technology";
    } else if (promptLower.includes("portfolio") || promptLower.includes("design") || promptLower.includes("creative")) {
      category = "design";
    } else if (promptLower.includes("store") || promptLower.includes("shop") || promptLower.includes("commerce")) {
      category = "shopping";
    } else if (promptLower.includes("agency") || promptLower.includes("corporate") || promptLower.includes("business")) {
      category = "business";
    }
  }

  // Replace missing or placeholder src attribute inside <img> tags with Unsplash images
  cleaned = cleaned.replace(/<img\s([^>]*?)>/gi, (match, attrs) => {
    const hasSrc = /src=["']([^"']*?)["']/i.test(attrs);
    let needsPlaceholder = false;
    let newAttrs = attrs;

    if (!hasSrc) {
      needsPlaceholder = true;
    } else {
      const srcMatch = attrs.match(/src=["']([^"']*?)["']/i);
      const srcVal = srcMatch ? srcMatch[1].trim() : "";
      if (!srcVal || srcVal === "#" || srcVal.includes("placeholder") || srcVal.includes("via.placeholder")) {
        needsPlaceholder = true;
      }
    }

    if (needsPlaceholder) {
      const randPhotoId = Math.floor(Math.random() * 900) + 100;
      const unsplashUrl = `https://images.unsplash.com/photo-${randPhotoId === 150 ? "1500000000000" : "1507525428034b"}?auto=format&fit=crop&w=1200&q=80&sig=${randPhotoId}&q=${encodeURIComponent(category)}`;
      
      if (hasSrc) {
        newAttrs = attrs.replace(/src=["']([^"']*?)["']/i, `src="${unsplashUrl}"`);
      } else {
        newAttrs += ` src="${unsplashUrl}"`;
      }
    }

    return `<img ${newAttrs.trim()}>`;
  });

  return cleaned.trim();
}

function generateVectorFaviconSvg(promptHint: string = ""): string {
  let colorStart = "#3b82f6";
  let colorEnd = "#1d4ed8";
  let letter = "S";

  if (promptHint) {
    const promptLower = promptHint.toLowerCase();
    const words = promptHint.trim().split(/\s+/);
    if (words.length > 0 && words[0]) {
      letter = words[0].charAt(0).toUpperCase();
    }
    if (promptLower.includes("coffee") || promptLower.includes("cafe")) {
      colorStart = "#f59e0b";
      colorEnd = "#78350f";
      letter = "☕";
    } else if (promptLower.includes("store") || promptLower.includes("shop") || promptLower.includes("checkout")) {
      colorStart = "#10b981";
      colorEnd = "#065f46";
      letter = "🛒";
    } else if (promptLower.includes("tech") || promptLower.includes("saas") || promptLower.includes("software")) {
      colorStart = "#3b82f6";
      colorEnd = "#1e3a8a";
      letter = "⚡";
    } else if (promptLower.includes("portfolio") || promptLower.includes("design")) {
      colorStart = "#ec4899";
      colorEnd = "#9d174d";
      letter = "🎨";
    } else if (promptLower.includes("restaurant") || promptLower.includes("food")) {
      colorStart = "#ef4444";
      colorEnd = "#991b1b";
      letter = "🍽️";
    }
  }

  const svg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">
    <defs>
      <linearGradient id="favGrad" x1="0%" y1="0%" x2="100%" y2="100%">
        <stop offset="0%" stop-color="${colorStart}" />
        <stop offset="100%" stop-color="${colorEnd}" />
      </linearGradient>
    </defs>
    <circle cx="50" cy="50" r="46" fill="url(#favGrad)" stroke="rgba(255,255,255,0.15)" stroke-width="4" />
    <text x="50" y="${letter.length === 1 && !/[\uD800-\uDFFF]./.test(letter) ? "65" : "58"}" font-family="'Inter', sans-serif" font-weight="900" font-size="44" fill="#ffffff" text-anchor="middle" dominant-baseline="middle">${letter}</text>
  </svg>`.replace(/\s+/g, " ");

  return `data:image/svg+xml;base64,${Buffer.from(svg).toString("base64")}`;
}

app.post("/api/publish_simulation", (req, res) => {
  try {
    const { html, title } = req.body;
    if (!html) {
      return res.status(400).json({ error: "No content to publish" });
    }

    // Self-Healing Page Check Loop
    let healedHtml = html;
    
    // Quick server-side regex audit for basic layout structure issues:
    // e.g., Unclosed divs or missing tags, unclosed quotes, etc.
    const openDivCount = (healedHtml.match(/<div/g) || []).length;
    const closeDivCount = (healedHtml.match(/<\/div>/g) || []).length;
    
    if (openDivCount > closeDivCount) {
      // Append missing closing tags
      const gap = openDivCount - closeDivCount;
      healedHtml += "</div>".repeat(gap);
    }

    // Media compressor simulation (Auto WebP replacement mock for simulation)
    // Replace any imported non-webp image formats with modern simulations
    healedHtml = healedHtml.replace(/\.(png|jpg|jpeg)/gi, ".webp");

    // Dynamic Asset Placeholder & Minifier run
    healedHtml = optimizeAndMinifyHtml(healedHtml, title || "");

    // Dynamic Favicon Generator & Embedding
    const faviconUri = generateVectorFaviconSvg(title || "");
    if (healedHtml.includes("sabis-canvas-body-wrap")) {
      healedHtml = healedHtml.replace(
        /class=['"]sabis-canvas-body-wrap['"][^>]*?>/i,
        `class="sabis-canvas-body-wrap" style="all: unset;">\n<link rel="shortcut icon" href="${faviconUri}" type="image/svg+xml">`
      );
    } else {
      healedHtml = `<link rel="shortcut icon" href="${faviconUri}" type="image/svg+xml">\n${healedHtml}`;
    }

    const pageSlug = (title || "sabis-visual-page")
      .toLowerCase()
      .trim()
      .replace(/[^a-z0-9]+/g, "-");

    const liveUrl = `${process.env.APP_URL || "https://example.com"}/preview/${pageSlug}`;

    return res.json({
      success: true,
      liveUrl,
      title: title || "Sabis AI Studio Layout",
      healedHtml,
      auditReport: `Audit complete. Div check: ${openDivCount} opened, ${closeDivCount} closed. Applied production regex minification and image pipeline placeholders. Embedded prompt-driven vector favicon shortcut in header metadata.`,
    });
  } catch (error: any) {
    return res.status(500).json({ error: "Failed to perform publishing audit" });
  }
});

// ----------------------------------------------------
// 5. API: Simulated Auto WebP Media Handler for local file drag & drop
// ----------------------------------------------------
app.post("/api/auto_webp_media_handler", (req, res) => {
  try {
    const { files } = req.body;
    if (!files || !Array.isArray(files)) {
      return res.status(400).json({ error: "Files array is required" });
    }

    const processedUrls = files.map(file => {
      let base64Url = file.base64;
      if (!base64Url.startsWith("data:")) {
        base64Url = `data:${file.type};base64,${base64Url}`;
      }
      return base64Url.replace(/(image\/jpeg|image\/png)/gi, "image/webp");
    });

    return res.json({ success: true, data: processedUrls });
  } catch (error: any) {
    return res.status(500).json({ error: error.message || "Failed to process dropped files" });
  }
});

// ----------------------------------------------------
// Vite setup and fallback logic
// ----------------------------------------------------
async function startServer() {
  if (process.env.NODE_ENV !== "production") {
    const vite = await createViteServer({
      server: { middlewareMode: true },
      appType: "spa",
    });
    app.use(vite.middlewares);
  } else {
    const distPath = path.join(process.cwd(), "dist");
    app.use(express.static(distPath));
    app.get("*", (req, res) => {
      res.sendFile(path.join(distPath, "index.html"));
    });
  }

  app.listen(PORT, "0.0.0.0", () => {
    console.log(`[Sabis AI Studio Server] initialized and running on http://localhost:${PORT}`);
  });
}

startServer();
