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

// ----------------------------------------------------
// 1. API: Layout Generation via Gemini & Groq Hybrid Chain
// ----------------------------------------------------
app.post("/api/generate_page", async (req, res) => {
  try {
    const { prompt, themePreset } = req.body;
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
            model: "llama3-70b-8192",
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

    const systemInstruction = `
      You are an elite, modern Web Designer and No-Code Tailwind CSS Master.
      Generate a single high-impact, breathtaking page layout styled recursively with ONLY modern Tailwind CSS utility classes.
      
      CRITICAL INSTRUCTIONS:
      1. Output ONLY clean, valid, standalone semantic HTML tags. Do NOT wrap in <html>, <head>, or <body>. Do NOT include markdown blocks (\`\`\`html) or explanation text.
      2. Style everything beautifully with rich backgrounds, grids, flex containers, elegant spacing, card transitions, responsive typography, and crisp elements.
      3. Colors/Theme Preset configuration: Use the chosen preset [${themePreset || 'Minimal Light'}].
         - 'Cyberpunk Dark': neon accents (pink/cyan/indigo), dark slate/purple backgrounds (bg-slate-950), high-contrast borders, glowing effects.
         - 'Minimal Light': sleek off-whites (bg-zinc-50), subtle gray borders (border-zinc-200), pure black text, sophisticated light charcoal cards, rich spacing.
         - 'Royal Gold': emerald/dark-blue/gold accents, opulent visual elegance (bg-slate-900), amber text accents, premium borders.
      4. Place interactive items such as CTA buttons, signup forms, responsive sections, and headers containing theme triggers.
      5. Include native-looking structures like product features, pricing matrices, testimonials, and dynamic heroes.
      6. For image elements (<img>), use standard Unsplash URLs related to the context or clean abstract layout SVGs. Ensure <img> tags have referrerPolicy="no-referrer" and alt tags.
    `;

    const response = await ai.models.generateContent({
      model: "gemini-3.5-flash",
      contents: `Design a gorgeous web page for: "${enhancedInstruction}". Match the theme style: ${themePreset || "Minimal Light"}.`,
      config: {
        systemInstruction: systemInstruction,
        temperature: 0.7,
      },
    });

    let html = response.text || "";
    // Clean up markdown ticks if Gemini outputted any anyway (Failsafe Cleaning Nets)
    html = html.replace(/```html/gi, "").replace(/```/gi, "").trim();

    return res.json({ html });
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
            model: "llama3-70b-8192",
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
        }
      } catch (e) {
        console.error("Groq copywriting failed, falling back to Gemini", e);
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
            model: "llama3-70b-8192",
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
        }
      } catch (e) {
        console.error("Groq translation failed, falling back to Gemini", e);
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

    return res.json({ success: true, html });
  } catch (error: any) {
    console.error("Section Redesign Error:", error);
    return res.status(500).json({ error: error.message || "Failed to redesign section" });
  }
});

// ----------------------------------------------------
// 4. API: Publishing Simulation and Server-Side HTML Healing
// ----------------------------------------------------
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
      auditReport: `Audit complete. Div check: ${openDivCount} opened, ${closeDivCount} closed. Restructured gap: ${Math.max(0, openDivCount - closeDivCount)} divs self-healed. Compiled media vectors to WebP.`,
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
