/*! driver.js 1.9.0 | MIT License | Copyright (c) Kamran Ahmed | https://github.com/nilbuild/driver.js */
/* Readable build of packages/driver/src at tag 1.9.0 (esbuild, not minified). The plugin loads js/driver.min.js, the official dist/driver.js.iife.js. */
"use strict";
var driver = driver || {};
driver.js = (() => {
  var __defProp = Object.defineProperty;
  var __getOwnPropDesc = Object.getOwnPropertyDescriptor;
  var __getOwnPropNames = Object.getOwnPropertyNames;
  var __hasOwnProp = Object.prototype.hasOwnProperty;
  var __export = (target, all) => {
    for (var name in all)
      __defProp(target, name, { get: all[name], enumerable: true });
  };
  var __copyProps = (to, from, except, desc) => {
    if (from && typeof from === "object" || typeof from === "function") {
      for (let key of __getOwnPropNames(from))
        if (!__hasOwnProp.call(to, key) && key !== except)
          __defProp(to, key, { get: () => from[key], enumerable: !(desc = __getOwnPropDesc(from, key)) || desc.enumerable });
    }
    return to;
  };
  var __toCommonJS = (mod) => __copyProps(__defProp({}, "__esModule", { value: true }), mod);

  // src/driver.ts
  var driver_exports = {};
  __export(driver_exports, {
    driver: () => driver
  });

  // src/click.ts
  var DRIVER_CLICK_EVENTS = ["pointerdown", "mousedown", "pointerup", "mouseup", "click"];
  var driverClickHandlers = /* @__PURE__ */ new WeakMap();
  function onDriverClick(element, listener, shouldPreventDefault) {
    destroyDriverClick(element);
    const handler = (e) => {
      const target = e.target;
      if (!element.contains(target)) {
        return;
      }
      if (!shouldPreventDefault || shouldPreventDefault(target)) {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
      }
      if (e.type === "click") {
        listener?.(e);
      }
    };
    const useCapture = true;
    for (const type of DRIVER_CLICK_EVENTS) {
      document.addEventListener(type, handler, useCapture);
    }
    driverClickHandlers.set(element, handler);
  }
  function destroyDriverClick(element) {
    const handler = driverClickHandlers.get(element);
    if (!handler) {
      return;
    }
    for (const type of DRIVER_CLICK_EVENTS) {
      document.removeEventListener(type, handler, true);
    }
    driverClickHandlers.delete(element);
  }

  // src/position.ts
  function getPopoverDimensions(popover, offset) {
    const boundingClientRect = popover.wrapper.getBoundingClientRect();
    return {
      width: boundingClientRect.width + offset,
      height: boundingClientRect.height + offset,
      realWidth: boundingClientRect.width,
      realHeight: boundingClientRect.height
    };
  }
  function calculateTopForLeftRight(alignment, config) {
    const { elementDimensions, popoverDimensions, popoverPadding, popoverArrowDimensions } = config;
    if (alignment === "start") {
      return Math.max(
        Math.min(
          elementDimensions.top - popoverPadding,
          window.innerHeight - popoverDimensions.realHeight - popoverArrowDimensions.width
        ),
        popoverArrowDimensions.width
      );
    }
    if (alignment === "end") {
      return Math.max(
        Math.min(
          elementDimensions.top - popoverDimensions?.realHeight + elementDimensions.height + popoverPadding,
          window.innerHeight - popoverDimensions?.realHeight - popoverArrowDimensions.width
        ),
        popoverArrowDimensions.width
      );
    }
    if (alignment === "center") {
      return Math.max(
        Math.min(
          elementDimensions.top + elementDimensions.height / 2 - popoverDimensions?.realHeight / 2,
          window.innerHeight - popoverDimensions?.realHeight - popoverArrowDimensions.width
        ),
        popoverArrowDimensions.width
      );
    }
    return 0;
  }
  function calculateLeftForTopBottom(alignment, config) {
    const { elementDimensions, popoverDimensions, popoverPadding, popoverArrowDimensions } = config;
    if (alignment === "start") {
      return Math.max(
        Math.min(
          elementDimensions.left - popoverPadding,
          window.innerWidth - popoverDimensions.realWidth - popoverArrowDimensions.width
        ),
        popoverArrowDimensions.width
      );
    }
    if (alignment === "end") {
      return Math.max(
        Math.min(
          elementDimensions.left - popoverDimensions?.realWidth + elementDimensions.width + popoverPadding,
          window.innerWidth - popoverDimensions?.realWidth - popoverArrowDimensions.width
        ),
        popoverArrowDimensions.width
      );
    }
    if (alignment === "center") {
      return Math.max(
        Math.min(
          elementDimensions.left + elementDimensions.width / 2 - popoverDimensions?.realWidth / 2,
          window.innerWidth - popoverDimensions?.realWidth - popoverArrowDimensions.width
        ),
        popoverArrowDimensions.width
      );
    }
    return 0;
  }
  function repositionPopover(popover, anchor, options) {
    const { align: requiredAlignment, side } = options;
    const requiredSide = options.centered ? "over" : side;
    const popoverPadding = options.padding;
    const popoverDimensions = getPopoverDimensions(popover, options.offset);
    const popoverArrowDimensions = popover.arrow.getBoundingClientRect();
    const elementDimensions = anchor.getBoundingClientRect();
    const topValue = elementDimensions.top - popoverDimensions.height;
    let isTopOptimal = topValue >= 0;
    const bottomValue = window.innerHeight - (elementDimensions.bottom + popoverDimensions.height);
    let isBottomOptimal = bottomValue >= 0;
    const leftValue = elementDimensions.left - popoverDimensions.width;
    let isLeftOptimal = leftValue >= 0;
    const rightValue = window.innerWidth - (elementDimensions.right + popoverDimensions.width);
    let isRightOptimal = rightValue >= 0;
    const noneOptimal = !isTopOptimal && !isBottomOptimal && !isLeftOptimal && !isRightOptimal;
    let popoverRenderedSide = requiredSide;
    if (requiredSide === "top" && isTopOptimal) {
      isRightOptimal = isLeftOptimal = isBottomOptimal = false;
    } else if (requiredSide === "bottom" && isBottomOptimal) {
      isRightOptimal = isLeftOptimal = isTopOptimal = false;
    } else if (requiredSide === "left" && isLeftOptimal) {
      isRightOptimal = isTopOptimal = isBottomOptimal = false;
    } else if (requiredSide === "right" && isRightOptimal) {
      isLeftOptimal = isTopOptimal = isBottomOptimal = false;
    }
    if (requiredSide === "over") {
      const leftToSet = window.innerWidth / 2 - popoverDimensions.realWidth / 2;
      const topToSet = window.innerHeight / 2 - popoverDimensions.realHeight / 2;
      popover.wrapper.style.left = `${leftToSet}px`;
      popover.wrapper.style.right = `auto`;
      popover.wrapper.style.top = `${topToSet}px`;
      popover.wrapper.style.bottom = `auto`;
    } else if (noneOptimal) {
      const leftValue2 = window.innerWidth / 2 - popoverDimensions?.realWidth / 2;
      const bottomValue2 = 10;
      popover.wrapper.style.left = `${leftValue2}px`;
      popover.wrapper.style.right = `auto`;
      popover.wrapper.style.bottom = `${bottomValue2}px`;
      popover.wrapper.style.top = `auto`;
    } else if (isLeftOptimal) {
      const leftToSet = Math.min(
        leftValue,
        window.innerWidth - popoverDimensions?.realWidth - popoverArrowDimensions.width
      );
      const topToSet = calculateTopForLeftRight(requiredAlignment, {
        elementDimensions,
        popoverDimensions,
        popoverPadding,
        popoverArrowDimensions
      });
      popover.wrapper.style.left = `${leftToSet}px`;
      popover.wrapper.style.top = `${topToSet}px`;
      popover.wrapper.style.bottom = `auto`;
      popover.wrapper.style.right = "auto";
      popoverRenderedSide = "left";
    } else if (isRightOptimal) {
      const rightToSet = Math.min(
        rightValue,
        window.innerWidth - popoverDimensions?.realWidth - popoverArrowDimensions.width
      );
      const topToSet = calculateTopForLeftRight(requiredAlignment, {
        elementDimensions,
        popoverDimensions,
        popoverPadding,
        popoverArrowDimensions
      });
      popover.wrapper.style.right = `${rightToSet}px`;
      popover.wrapper.style.top = `${topToSet}px`;
      popover.wrapper.style.bottom = `auto`;
      popover.wrapper.style.left = "auto";
      popoverRenderedSide = "right";
    } else if (isTopOptimal) {
      const topToSet = Math.min(
        topValue,
        window.innerHeight - popoverDimensions.realHeight - popoverArrowDimensions.width
      );
      let leftToSet = calculateLeftForTopBottom(requiredAlignment, {
        elementDimensions,
        popoverDimensions,
        popoverPadding,
        popoverArrowDimensions
      });
      popover.wrapper.style.top = `${topToSet}px`;
      popover.wrapper.style.left = `${leftToSet}px`;
      popover.wrapper.style.bottom = `auto`;
      popover.wrapper.style.right = "auto";
      popoverRenderedSide = "top";
    } else if (isBottomOptimal) {
      const bottomToSet = Math.min(
        bottomValue,
        window.innerHeight - popoverDimensions?.realHeight - popoverArrowDimensions.width
      );
      let leftToSet = calculateLeftForTopBottom(requiredAlignment, {
        elementDimensions,
        popoverDimensions,
        popoverPadding,
        popoverArrowDimensions
      });
      popover.wrapper.style.left = `${leftToSet}px`;
      popover.wrapper.style.bottom = `${bottomToSet}px`;
      popover.wrapper.style.top = `auto`;
      popover.wrapper.style.right = "auto";
      popoverRenderedSide = "bottom";
    }
    renderPopoverArrow(popover, noneOptimal ? "over" : popoverRenderedSide, requiredAlignment, anchor);
    [...popover.wrapper.classList].filter((className) => className.startsWith("driver-popover-side-") || className.startsWith("driver-popover-align-")).forEach((className) => popover.wrapper.classList.remove(className));
    popover.wrapper.classList.add(`driver-popover-side-${popoverRenderedSide}`);
    popover.wrapper.classList.add(`driver-popover-align-${requiredAlignment}`);
  }
  var ARROW_SIZE = 10;
  var ARROW_CORNER_INSET = 15;
  function calculateArrowTarget(elementStart, elementEnd, popoverStart, popoverEnd, alignment, arrowSize = ARROW_SIZE) {
    const popoverLength = popoverEnd - popoverStart;
    const fullySpansPopover = elementStart <= popoverStart && elementEnd >= popoverEnd;
    if (fullySpansPopover) {
      if (alignment === "start") {
        return ARROW_CORNER_INSET + arrowSize / 2;
      }
      if (alignment === "end") {
        return popoverLength - ARROW_CORNER_INSET - arrowSize / 2;
      }
      return popoverLength / 2;
    }
    const overlapStart = Math.min(Math.max(elementStart, popoverStart), popoverEnd);
    const overlapEnd = Math.min(Math.max(elementEnd, popoverStart), popoverEnd);
    return (overlapStart + overlapEnd) / 2 - popoverStart;
  }
  function calculateArrowOffset(targetCenter, popoverLength, arrowSize = ARROW_SIZE) {
    const minOffset = ARROW_CORNER_INSET;
    const maxOffset = popoverLength - ARROW_CORNER_INSET - arrowSize;
    if (maxOffset < minOffset) {
      return Math.max(0, (popoverLength - arrowSize) / 2);
    }
    const offset = targetCenter - arrowSize / 2;
    return Math.min(Math.max(offset, minOffset), maxOffset);
  }
  function resolveArrowSide(side, element, popover) {
    if (side === "left" || side === "right") {
      const overlapsVertically = element.bottom > popover.top && element.top < popover.bottom;
      if (overlapsVertically) {
        return side;
      }
      return element.bottom <= popover.top ? "bottom" : "top";
    }
    const overlapsHorizontally = element.right > popover.left && element.left < popover.right;
    if (overlapsHorizontally) {
      return side;
    }
    return element.right <= popover.left ? "right" : "left";
  }
  function renderPopoverArrow(popover, side, alignment, anchor) {
    const popoverArrow = popover.arrow;
    popoverArrow.className = "driver-popover-arrow";
    popoverArrow.style.top = "";
    popoverArrow.style.right = "";
    popoverArrow.style.bottom = "";
    popoverArrow.style.left = "";
    if (side === "over") {
      popoverArrow.classList.add("driver-popover-arrow-none");
      return;
    }
    const elementRect = anchor.getBoundingClientRect();
    const popoverRect = popover.wrapper.getBoundingClientRect();
    const arrowSide = resolveArrowSide(side, elementRect, popoverRect);
    popoverArrow.classList.add(`driver-popover-arrow-side-${arrowSide}`);
    const arrowSize = popoverArrow.getBoundingClientRect().width || ARROW_SIZE;
    if (arrowSide === "left" || arrowSide === "right") {
      const target = calculateArrowTarget(
        elementRect.top,
        elementRect.bottom,
        popoverRect.top,
        popoverRect.bottom,
        alignment,
        arrowSize
      );
      popoverArrow.style.top = `${calculateArrowOffset(target, popoverRect.height, arrowSize)}px`;
    } else {
      const target = calculateArrowTarget(
        elementRect.left,
        elementRect.right,
        popoverRect.left,
        popoverRect.right,
        alignment,
        arrowSize
      );
      popoverArrow.style.left = `${calculateArrowOffset(target, popoverRect.width, arrowSize)}px`;
    }
  }

  // src/utils.ts
  function resolveElement(element) {
    if (typeof element === "function") {
      return element();
    }
    if (typeof element === "string") {
      return document.querySelector(element);
    }
    return element;
  }
  function isScrollable(element) {
    const style = window.getComputedStyle(element);
    return [style.overflow, style.overflowX, style.overflowY].some((value) => {
      return value === "auto" || value === "scroll";
    });
  }
  function easeInOutQuad(elapsed, initialValue, amountOfChange, duration) {
    if ((elapsed /= duration / 2) < 1) {
      return amountOfChange / 2 * elapsed * elapsed + initialValue;
    }
    return -amountOfChange / 2 * (--elapsed * (elapsed - 2) - 1) + initialValue;
  }
  function getFocusableElements(parentEls) {
    const focusableQuery = 'a[href]:not([disabled]), button:not([disabled]), textarea:not([disabled]), input[type="text"]:not([disabled]), input[type="radio"]:not([disabled]), input[type="checkbox"]:not([disabled]), select:not([disabled])';
    return parentEls.flatMap((parentEl) => {
      const isParentFocusable = parentEl.matches(focusableQuery);
      const focusableEls = Array.from(parentEl.querySelectorAll(focusableQuery));
      return [...isParentFocusable ? [parentEl] : [], ...focusableEls];
    }).filter((el) => {
      return getComputedStyle(el).pointerEvents !== "none" && isElementVisible(el);
    });
  }
  function bringInView(element, shouldSmoothScroll) {
    if (!element || isElementInView(element)) {
      return;
    }
    const isTallerThanViewport = element.offsetHeight > window.innerHeight;
    element.scrollIntoView({
      // Removing the smooth scrolling for elements which exist inside the scrollable parent
      // This was causing the highlight to not properly render
      behavior: !shouldSmoothScroll || hasScrollableParent(element) ? "auto" : "smooth",
      inline: "center",
      block: isTallerThanViewport ? "start" : "center"
    });
  }
  function hasScrollableParent(e) {
    if (!e || !e.parentElement) {
      return;
    }
    const parent = e.parentElement;
    return parent.scrollHeight > parent.clientHeight;
  }
  function isElementInView(element) {
    const rect = element.getBoundingClientRect();
    return rect.top >= 0 && rect.left >= 0 && rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) && rect.right <= (window.innerWidth || document.documentElement.clientWidth);
  }
  function isElementVisible(el) {
    return !!(el.offsetWidth || el.offsetHeight || el.getClientRects().length);
  }

  // src/popover.ts
  function hidePopover(popover) {
    if (!popover) {
      return;
    }
    popover.wrapper.style.display = "none";
  }
  function renderPopover(anchor, options) {
    const popover = createPopover();
    document.body.appendChild(popover.wrapper);
    const { title, description, showButtons, disableButtons, showProgress, nextBtnText, prevBtnText, progressText } = options;
    popover.nextButton.innerHTML = nextBtnText;
    popover.previousButton.innerHTML = prevBtnText;
    popover.progress.innerHTML = progressText;
    popover.closeButton.setAttribute("aria-label", options.closeBtnLabel);
    if (options.doneButton) {
      popover.nextButton.classList.add("driver-popover-done-btn");
    }
    if (title) {
      popover.title.innerHTML = title;
      popover.title.style.display = "block";
    } else {
      popover.title.style.display = "none";
    }
    if (description) {
      popover.description.innerHTML = description;
      popover.description.style.display = "block";
    } else {
      popover.description.style.display = "none";
    }
    const showFooter = showButtons.includes("next") || showButtons.includes("previous") || showProgress;
    popover.closeButton.style.display = showButtons.includes("close") ? "block" : "none";
    if (showFooter) {
      popover.footer.style.display = "flex";
      popover.progress.style.display = showProgress ? "block" : "none";
      popover.nextButton.style.display = showButtons.includes("next") ? "block" : "none";
      popover.previousButton.style.display = showButtons.includes("previous") ? "block" : "none";
    } else {
      popover.footer.style.display = "none";
    }
    if (disableButtons.includes("next")) {
      popover.nextButton.disabled = true;
      popover.nextButton.classList.add("driver-popover-btn-disabled");
    }
    if (disableButtons.includes("previous")) {
      popover.previousButton.disabled = true;
      popover.previousButton.classList.add("driver-popover-btn-disabled");
    }
    if (disableButtons.includes("close")) {
      popover.closeButton.disabled = true;
      popover.closeButton.classList.add("driver-popover-btn-disabled");
    }
    const popoverWrapper = popover.wrapper;
    popoverWrapper.style.display = "block";
    popoverWrapper.style.left = "";
    popoverWrapper.style.top = "";
    popoverWrapper.style.bottom = "";
    popoverWrapper.style.right = "";
    popoverWrapper.id = "driver-popover-content";
    popoverWrapper.setAttribute("role", "dialog");
    popoverWrapper.setAttribute("aria-labelledby", "driver-popover-title");
    popoverWrapper.setAttribute("aria-describedby", "driver-popover-description");
    const popoverArrow = popover.arrow;
    popoverArrow.className = "driver-popover-arrow";
    popoverWrapper.className = `driver-popover ${options.popoverClass || ""}`.trim();
    onDriverClick(
      popover.wrapper,
      (e) => {
        const target = e.target;
        if (!!target.closest(".driver-popover-next-btn")) {
          return options.onNextClick?.();
        }
        if (!!target.closest(".driver-popover-prev-btn")) {
          return options.onPrevClick?.();
        }
        if (!!target.closest(".driver-popover-close-btn")) {
          return options.onCloseClick?.();
        }
        return void 0;
      },
      (target) => {
        if (popover.description.contains(target) || popover.title.contains(target)) {
          return false;
        }
        return !!target.closest(".driver-popover-prev-btn, .driver-popover-next-btn, .driver-popover-close-btn");
      }
    );
    options.onRender?.(popover);
    repositionPopover(popover, anchor, options.position);
    repositionOnImagesLoad(popover, anchor, options.position);
    bringInView(popoverWrapper, options.smoothScroll);
    const focusableElement = getFocusableElements([popoverWrapper, anchor]);
    if (focusableElement.length > 0) {
      focusableElement[0].focus();
    }
    return popover;
  }
  function repositionOnImagesLoad(popover, anchor, position) {
    const images = popover.wrapper.querySelectorAll("img");
    images.forEach((image) => {
      if (image.complete) {
        return;
      }
      const reposition = () => repositionPopover(popover, anchor, position);
      image.addEventListener("load", reposition, { once: true });
      image.addEventListener("error", reposition, { once: true });
    });
  }
  function createPopover() {
    const wrapper = document.createElement("div");
    wrapper.classList.add("driver-popover");
    const arrow = document.createElement("div");
    arrow.classList.add("driver-popover-arrow");
    const title = document.createElement("header");
    title.id = "driver-popover-title";
    title.classList.add("driver-popover-title");
    title.style.display = "none";
    title.innerText = "Popover Title";
    const description = document.createElement("div");
    description.id = "driver-popover-description";
    description.classList.add("driver-popover-description");
    description.style.display = "none";
    description.innerText = "Popover description is here";
    const closeButton = document.createElement("button");
    closeButton.type = "button";
    closeButton.classList.add("driver-popover-close-btn");
    closeButton.innerHTML = "&times;";
    const footer = document.createElement("footer");
    footer.classList.add("driver-popover-footer");
    const progress = document.createElement("span");
    progress.classList.add("driver-popover-progress-text");
    progress.innerText = "";
    const footerButtons = document.createElement("span");
    footerButtons.classList.add("driver-popover-navigation-btns");
    const previousButton = document.createElement("button");
    previousButton.type = "button";
    previousButton.classList.add("driver-popover-prev-btn", "driver-popover-footer-btn");
    previousButton.innerHTML = "Previous";
    const nextButton = document.createElement("button");
    nextButton.type = "button";
    nextButton.classList.add("driver-popover-next-btn", "driver-popover-footer-btn");
    nextButton.innerHTML = "Next";
    footerButtons.appendChild(previousButton);
    footerButtons.appendChild(nextButton);
    footer.appendChild(progress);
    footer.appendChild(footerButtons);
    wrapper.appendChild(closeButton);
    wrapper.appendChild(arrow);
    wrapper.appendChild(title);
    wrapper.appendChild(description);
    wrapper.appendChild(footer);
    return {
      wrapper,
      arrow,
      title,
      description,
      footer,
      previousButton,
      nextButton,
      closeButton,
      footerButtons,
      progress
    };
  }
  function destroyPopover(popover) {
    if (!popover) {
      return;
    }
    destroyDriverClick(popover.wrapper);
    popover.wrapper.parentElement?.removeChild(popover.wrapper);
  }

  // src/stage.ts
  function generateStageSvgPathString(stage, options) {
    const windowX = window.innerWidth;
    const windowY = window.innerHeight;
    const stagePadding = options.padding;
    const stageRadius = options.radius;
    const stageWidth = stage.width + stagePadding * 2;
    const stageHeight = stage.height + stagePadding * 2;
    const limitedRadius = Math.min(stageRadius, stageWidth / 2, stageHeight / 2);
    const normalizedRadius = Math.floor(Math.max(limitedRadius, 0));
    const highlightBoxX = stage.x - stagePadding + normalizedRadius;
    const highlightBoxY = stage.y - stagePadding;
    const highlightBoxWidth = stageWidth - normalizedRadius * 2;
    const highlightBoxHeight = stageHeight - normalizedRadius * 2;
    return `M${windowX},0L0,0L0,${windowY}L${windowX},${windowY}L${windowX},0Z
    M${highlightBoxX},${highlightBoxY} h${highlightBoxWidth} a${normalizedRadius},${normalizedRadius} 0 0 1 ${normalizedRadius},${normalizedRadius} v${highlightBoxHeight} a${normalizedRadius},${normalizedRadius} 0 0 1 -${normalizedRadius},${normalizedRadius} h-${highlightBoxWidth} a${normalizedRadius},${normalizedRadius} 0 0 1 -${normalizedRadius},-${normalizedRadius} v-${highlightBoxHeight} a${normalizedRadius},${normalizedRadius} 0 0 1 ${normalizedRadius},-${normalizedRadius} z`;
  }

  // src/overlay.ts
  function transitionStage(ctx, elapsed, duration, from, to) {
    let activeStagePosition = ctx.getState("__activeStagePosition");
    const fromDefinition = activeStagePosition ? activeStagePosition : from.getBoundingClientRect();
    const toDefinition = to.getBoundingClientRect();
    const x = easeInOutQuad(elapsed, fromDefinition.x, toDefinition.x - fromDefinition.x, duration);
    const y = easeInOutQuad(elapsed, fromDefinition.y, toDefinition.y - fromDefinition.y, duration);
    const width = easeInOutQuad(elapsed, fromDefinition.width, toDefinition.width - fromDefinition.width, duration);
    const height = easeInOutQuad(elapsed, fromDefinition.height, toDefinition.height - fromDefinition.height, duration);
    activeStagePosition = {
      x,
      y,
      width,
      height
    };
    renderOverlay(ctx, activeStagePosition);
    ctx.setState("__activeStagePosition", activeStagePosition);
  }
  function trackActiveElement(ctx, element) {
    if (!element) {
      return;
    }
    const definition = element.getBoundingClientRect();
    const activeStagePosition = {
      x: definition.x,
      y: definition.y,
      width: definition.width,
      height: definition.height
    };
    ctx.setState("__activeStagePosition", activeStagePosition);
    renderOverlay(ctx, activeStagePosition);
  }
  function refreshOverlay(ctx) {
    const activeStagePosition = ctx.getState("__activeStagePosition");
    const overlaySvg = ctx.getState("__overlaySvg");
    if (!activeStagePosition) {
      return;
    }
    if (!overlaySvg) {
      console.warn("No stage svg found.");
      return;
    }
    const windowX = window.innerWidth;
    const windowY = window.innerHeight;
    overlaySvg.setAttribute("viewBox", `0 0 ${windowX} ${windowY}`);
  }
  function mountOverlay(ctx, stagePosition) {
    const overlaySvg = createOverlaySvg(ctx, stagePosition);
    document.body.appendChild(overlaySvg);
    onDriverClick(overlaySvg, (e) => {
      const target = e.target;
      if (target.tagName !== "path") {
        return;
      }
      ctx.emit("overlayClick");
    });
    ctx.setState("__overlaySvg", overlaySvg);
  }
  function renderOverlay(ctx, stagePosition) {
    const overlaySvg = ctx.getState("__overlaySvg");
    if (!overlaySvg) {
      mountOverlay(ctx, stagePosition);
      return;
    }
    const pathElement = overlaySvg.firstElementChild;
    if (pathElement?.tagName !== "path") {
      throw new Error("no path element found in stage svg");
    }
    pathElement.setAttribute("d", generateStageSvgPathString(stagePosition, stageOptions(ctx)));
  }
  function stageOptions(ctx) {
    if (ctx.getState("activeElement")?.id === "driver-dummy-element") {
      return { padding: 0, radius: 0 };
    }
    return {
      padding: ctx.getConfig("stagePadding") || 0,
      radius: ctx.getConfig("stageRadius") || 0
    };
  }
  function createOverlaySvg(ctx, stage) {
    const windowX = window.innerWidth;
    const windowY = window.innerHeight;
    const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
    svg.classList.add("driver-overlay", "driver-overlay-animated");
    svg.setAttribute("viewBox", `0 0 ${windowX} ${windowY}`);
    svg.setAttribute("xmlSpace", "preserve");
    svg.setAttribute("xmlnsXlink", "http://www.w3.org/1999/xlink");
    svg.setAttribute("version", "1.1");
    svg.setAttribute("preserveAspectRatio", "xMinYMin slice");
    svg.style.fillRule = "evenodd";
    svg.style.clipRule = "evenodd";
    svg.style.strokeLinejoin = "round";
    svg.style.strokeMiterlimit = "2";
    svg.style.zIndex = "10000";
    svg.style.position = "fixed";
    svg.style.top = "0";
    svg.style.left = "0";
    svg.style.width = "100%";
    svg.style.height = "100%";
    const stagePath = document.createElementNS("http://www.w3.org/2000/svg", "path");
    stagePath.setAttribute("d", generateStageSvgPathString(stage, stageOptions(ctx)));
    stagePath.style.fill = ctx.getConfig("overlayColor") || "rgb(0,0,0)";
    stagePath.style.opacity = `${ctx.getConfig("overlayOpacity")}`;
    stagePath.style.pointerEvents = "auto";
    stagePath.style.cursor = "auto";
    svg.appendChild(stagePath);
    return svg;
  }
  function destroyOverlay(ctx) {
    const overlaySvg = ctx.getState("__overlaySvg");
    if (overlaySvg) {
      destroyDriverClick(overlaySvg);
      overlaySvg.remove();
    }
  }

  // src/step.ts
  var DEFAULT_PROGRESS_TEXT = "{{current}} of {{total}}";
  function shouldSkipStep(ctx, step) {
    const skip = step.skipMissingElement ?? ctx.getConfig("skipMissingElement");
    if (!skip || !step.element) {
      return false;
    }
    return !resolveElement(step.element);
  }
  function findReachableIndex(ctx, fromIndex, direction) {
    const steps = ctx.getConfig("steps") || [];
    for (let i = fromIndex; i >= 0 && i < steps.length; i += direction) {
      if (!shouldSkipStep(ctx, steps[i])) {
        return i;
      }
    }
    return void 0;
  }
  function resolveNextHook(ctx, step) {
    const activeIndex = ctx.getState("activeIndex");
    const isLastStep = activeIndex !== void 0 && findReachableIndex(ctx, activeIndex + 1, 1) === void 0;
    const onDoneClick = step?.popover?.onDoneClick || ctx.getConfig("onDoneClick");
    if (isLastStep && onDoneClick) {
      return onDoneClick;
    }
    return step?.popover?.onNextClick || ctx.getConfig("onNextClick");
  }
  function resolvePrevHook(ctx, step) {
    return step?.popover?.onPrevClick || ctx.getConfig("onPrevClick");
  }
  function resolveCloseHook(ctx, step) {
    return step?.popover?.onCloseClick || ctx.getConfig("onCloseClick");
  }
  function resolveTourStep(ctx, stepIndex, defaults) {
    const steps = ctx.getConfig("steps") || [];
    const step = steps[stepIndex];
    const popover = step.popover || {};
    const hasNextStep = findReachableIndex(ctx, stepIndex + 1, 1) !== void 0;
    const hasPreviousStep = findReachableIndex(ctx, stepIndex - 1, -1) !== void 0;
    const doneBtnText = popover.doneBtnText || ctx.getConfig("doneBtnText") || "Done";
    const allowsClosing = ctx.getConfig("allowClose");
    const showProgress = typeof popover.showProgress !== "undefined" ? popover.showProgress : ctx.getConfig("showProgress");
    const progressText = popover.progressText || ctx.getConfig("progressText") || DEFAULT_PROGRESS_TEXT;
    const progressTextReplaced = progressText.replace("{{current}}", `${stepIndex + 1}`).replace("{{total}}", `${steps.length}`);
    const configuredButtons = popover.showButtons || ctx.getConfig("showButtons");
    const calculatedButtons = [
      "next",
      "previous",
      ...allowsClosing ? ["close"] : []
    ].filter((b) => {
      return !configuredButtons?.length || configuredButtons.includes(b);
    });
    const onNextClick = popover.onNextClick || ctx.getConfig("onNextClick");
    const onPrevClick = popover.onPrevClick || ctx.getConfig("onPrevClick");
    const onCloseClick = popover.onCloseClick || ctx.getConfig("onCloseClick");
    return {
      ...step,
      popover: {
        showButtons: calculatedButtons,
        nextBtnText: !hasNextStep ? doneBtnText : void 0,
        disableButtons: [
          ...ctx.getConfig("disableButtons") || [],
          ...!hasPreviousStep ? ["previous"] : []
        ],
        showProgress,
        onNextClick: onNextClick ? onNextClick : defaults.onNextClick,
        onPrevClick: onPrevClick ? onPrevClick : defaults.onPrevClick,
        onCloseClick: onCloseClick ? onCloseClick : defaults.onCloseClick,
        ...popover,
        progressText: progressTextReplaced
      }
    };
  }
  function resolveStepButtons(ctx, step) {
    return {
      showButtons: step?.popover?.showButtons || ctx.getConfig("showButtons") || [],
      disableButtons: step?.popover?.disableButtons || ctx.getConfig("disableButtons") || []
    };
  }
  function resolveStepPosition(ctx, element, step) {
    const stagePadding = ctx.getConfig("stagePadding") || 0;
    return {
      side: step.popover?.side || "bottom",
      align: step.popover?.align || "start",
      // The popover clears the highlight cutout (stagePadding) plus the
      // configured gap between the two.
      offset: stagePadding + (ctx.getConfig("popoverOffset") || 0),
      padding: stagePadding,
      // Without a real element the tour highlights a dummy element at the center
      // of the screen, and the popover is centered over it like a modal.
      centered: element.id === "driver-dummy-element"
    };
  }
  function resolveStepPopover(ctx, element, step) {
    const popover = step.popover || {};
    const activeIndex = ctx.getState("activeIndex");
    const isDoneStep = activeIndex !== void 0 && findReachableIndex(ctx, activeIndex + 1, 1) === void 0;
    return {
      title: popover.title,
      description: popover.description,
      ...resolveStepButtons(ctx, step),
      showProgress: popover.showProgress || ctx.getConfig("showProgress") || false,
      progressText: popover.progressText ?? (ctx.getConfig("progressText") || DEFAULT_PROGRESS_TEXT),
      nextBtnText: popover.nextBtnText ?? (ctx.getConfig("nextBtnText") || "Next"),
      prevBtnText: popover.prevBtnText ?? (ctx.getConfig("prevBtnText") || "Previous"),
      closeBtnLabel: popover.closeBtnLabel ?? (ctx.getConfig("closeBtnLabel") || "Close"),
      doneButton: isDoneStep,
      popoverClass: popover.popoverClass || ctx.getConfig("popoverClass") || "",
      smoothScroll: ctx.getConfig("smoothScroll"),
      // The hooks are resolved when the button is clicked rather than up front,
      // so a setConfig() between render and click is still picked up.
      onNextClick: () => {
        const onNextClick = resolveNextHook(ctx, step);
        if (onNextClick) {
          return onNextClick(element, step, ctx.getHookOpts());
        }
        return ctx.emit("nextClick");
      },
      onPrevClick: () => {
        const onPrevClick = resolvePrevHook(ctx, step);
        if (onPrevClick) {
          return onPrevClick(element, step, ctx.getHookOpts());
        }
        return ctx.emit("prevClick");
      },
      onCloseClick: () => {
        const onCloseClick = resolveCloseHook(ctx, step);
        if (onCloseClick) {
          return onCloseClick(element, step, ctx.getHookOpts());
        }
        return ctx.emit("closeClick");
      },
      onRender: (popoverDom) => {
        ctx.setState("popover", popoverDom);
        const onPopoverRender = popover.onPopoverRender || ctx.getConfig("onPopoverRender");
        onPopoverRender?.(popoverDom, ctx.getHookOpts());
      },
      position: resolveStepPosition(ctx, element, step)
    };
  }
  function renderStepPopover(ctx, element, step) {
    destroyPopover(ctx.getState("popover"));
    renderPopover(element, resolveStepPopover(ctx, element, step));
  }
  function repositionStepPopover(ctx, element, step) {
    const popover = ctx.getState("popover");
    if (!popover) {
      return;
    }
    repositionPopover(popover, element, resolveStepPosition(ctx, element, step));
  }

  // src/highlight.ts
  var POPOVER_ARIA = {
    "aria-haspopup": "dialog",
    "aria-expanded": "true",
    "aria-controls": "driver-popover-content"
  };
  var originalAria = /* @__PURE__ */ new WeakMap();
  function setPopoverAria(element) {
    if (!originalAria.has(element)) {
      const original = Object.fromEntries(Object.keys(POPOVER_ARIA).map((name) => [name, element.getAttribute(name)]));
      originalAria.set(element, original);
    }
    for (const [name, value] of Object.entries(POPOVER_ARIA)) {
      element.setAttribute(name, value);
    }
  }
  function restoreAria(element) {
    const original = originalAria.get(element);
    if (!original) {
      return;
    }
    for (const [name, value] of Object.entries(original)) {
      if (value === null) {
        element.removeAttribute(name);
      } else {
        element.setAttribute(name, value);
      }
    }
    originalAria.delete(element);
  }
  function mountDummyElement() {
    const existingDummy = document.getElementById("driver-dummy-element");
    if (existingDummy) {
      return existingDummy;
    }
    let element = document.createElement("div");
    element.id = "driver-dummy-element";
    element.style.width = "0";
    element.style.height = "0";
    element.style.pointerEvents = "none";
    element.style.opacity = "0";
    element.style.position = "fixed";
    element.style.top = "50%";
    element.style.left = "50%";
    document.body.appendChild(element);
    return element;
  }
  function highlight(ctx, step) {
    let elemObj = resolveElement(step.element);
    if (!elemObj) {
      elemObj = mountDummyElement();
    }
    transferHighlight(ctx, elemObj, step);
  }
  function refreshActiveHighlight(ctx) {
    const activeHighlight = ctx.getState("__activeElement");
    const activeStep = ctx.getState("__activeStep");
    if (!activeHighlight) {
      return;
    }
    trackActiveElement(ctx, activeHighlight);
    refreshOverlay(ctx);
    repositionStepPopover(ctx, activeHighlight, activeStep);
  }
  function transferHighlight(ctx, toElement, toStep) {
    const duration = ctx.getConfig("duration") || 400;
    const start = Date.now();
    const fromStep = ctx.getState("__activeStep");
    const fromElement = ctx.getState("__activeElement") || toElement;
    const isFirstHighlight = !fromElement || fromElement === toElement;
    const isToDummyElement = toElement.id === "driver-dummy-element";
    const isFromDummyElement = fromElement.id === "driver-dummy-element";
    const isAnimatedTour = ctx.getConfig("animate");
    const highlightStartedHook = toStep.onHighlightStarted || ctx.getConfig("onHighlightStarted");
    const highlightedHook = toStep?.onHighlighted || ctx.getConfig("onHighlighted");
    const deselectedHook = fromStep?.onDeselected || ctx.getConfig("onDeselected");
    const hookOpts = ctx.getHookOpts();
    if (fromStep && fromStep !== toStep && deselectedHook) {
      deselectedHook(isFromDummyElement ? void 0 : fromElement, fromStep, hookOpts);
    }
    if (highlightStartedHook) {
      highlightStartedHook(isToDummyElement ? void 0 : toElement, toStep, hookOpts);
    }
    const hasDelayedPopover = !isFirstHighlight && isAnimatedTour;
    let isPopoverRendered = false;
    hidePopover(ctx.getState("popover"));
    ctx.setState("previousStep", fromStep);
    ctx.setState("previousElement", fromElement);
    ctx.setState("activeStep", toStep);
    ctx.setState("activeElement", toElement);
    const animate = () => {
      const transitionCallback = ctx.getState("__transitionCallback");
      if (transitionCallback !== animate) {
        return;
      }
      const elapsed = Date.now() - start;
      const timeRemaining = duration - elapsed;
      const isHalfwayThrough = timeRemaining <= duration / 2;
      if (toStep.popover && isHalfwayThrough && !isPopoverRendered && hasDelayedPopover) {
        renderStepPopover(ctx, toElement, toStep);
        isPopoverRendered = true;
      }
      if (ctx.getConfig("animate") && elapsed < duration) {
        transitionStage(ctx, elapsed, duration, fromElement, toElement);
      } else {
        trackActiveElement(ctx, toElement);
        if (highlightedHook) {
          highlightedHook(isToDummyElement ? void 0 : toElement, toStep, ctx.getHookOpts());
        }
        ctx.setState("__transitionCallback", void 0);
        ctx.setState("__previousStep", fromStep);
        ctx.setState("__previousElement", fromElement);
        ctx.setState("__activeStep", toStep);
        ctx.setState("__activeElement", toElement);
      }
      window.requestAnimationFrame(animate);
    };
    ctx.setState("__transitionCallback", animate);
    window.requestAnimationFrame(animate);
    bringInView(toElement, ctx.getConfig("smoothScroll"));
    if (!hasDelayedPopover && toStep.popover) {
      renderStepPopover(ctx, toElement, toStep);
    }
    document.querySelectorAll(".driver-active-element-parent").forEach((element) => {
      element.classList.remove("driver-active-element-parent", "driver-active-element-parent-no-scroll");
    });
    document.querySelectorAll(".driver-active-element").forEach((element) => {
      element.classList.remove("driver-active-element", "driver-no-interaction");
      restoreAria(element);
    });
    const disableActiveInteraction = toStep.disableActiveInteraction ?? ctx.getConfig("disableActiveInteraction");
    if (disableActiveInteraction) {
      toElement.classList.add("driver-no-interaction");
    }
    const toParent = toElement.parentElement;
    if (toParent && toParent !== document.body) {
      toParent.classList.add("driver-active-element-parent");
      if (isScrollable(toParent)) {
        toParent.classList.add("driver-active-element-parent-no-scroll");
      }
    }
    toElement.classList.add("driver-active-element");
    setPopoverAria(toElement);
  }
  function destroyHighlight() {
    document.getElementById("driver-dummy-element")?.remove();
    document.querySelectorAll(".driver-active-element").forEach((element) => {
      const parent = element.parentElement;
      if (parent && parent !== document.body) {
        parent.classList.remove("driver-active-element-parent", "driver-active-element-parent-no-scroll");
      }
      element.classList.remove("driver-active-element", "driver-no-interaction");
      restoreAria(element);
    });
  }

  // src/events.ts
  function requireRefresh(ctx) {
    const resizeTimeout = ctx.getState("__resizeTimeout");
    if (resizeTimeout) {
      window.cancelAnimationFrame(resizeTimeout);
    }
    ctx.setState(
      "__resizeTimeout",
      window.requestAnimationFrame(() => refreshActiveHighlight(ctx))
    );
  }
  function trapFocus(ctx, e) {
    const isActivated = ctx.getState("isInitialized");
    if (!isActivated) {
      return;
    }
    const isTabKey = e.key === "Tab" || e.keyCode === 9;
    if (!isTabKey) {
      return;
    }
    const activeElement = ctx.getState("__activeElement");
    const popoverEl = ctx.getState("popover")?.wrapper;
    const focusableEls = getFocusableElements([
      ...popoverEl ? [popoverEl] : [],
      ...activeElement ? [activeElement] : []
    ]);
    const firstFocusableEl = focusableEls[0];
    const lastFocusableEl = focusableEls[focusableEls.length - 1];
    e.preventDefault();
    if (e.shiftKey) {
      const previousFocusableEl = focusableEls[focusableEls.indexOf(document.activeElement) - 1] || lastFocusableEl;
      previousFocusableEl?.focus();
    } else {
      const nextFocusableEl = focusableEls[focusableEls.indexOf(document.activeElement) + 1] || firstFocusableEl;
      nextFocusableEl?.focus();
    }
  }
  function onKeyup(ctx, e) {
    const allowKeyboardControl = ctx.getConfig("allowKeyboardControl") ?? true;
    if (!allowKeyboardControl) {
      return;
    }
    if (e.key === "Escape") {
      ctx.emit("escapePress");
    } else if (e.key === "ArrowRight") {
      ctx.emit("arrowRightPress");
    } else if (e.key === "ArrowLeft") {
      ctx.emit("arrowLeftPress");
    }
  }
  function onDocumentClick(ctx, e) {
    const activeElement = ctx.getState("__activeElement");
    const target = e.target;
    if (!activeElement || !target || !activeElement.contains(target)) {
      return;
    }
    ctx.emit("activeElementClick");
  }
  function initEvents(ctx) {
    const onWindowKeyup = (e) => onKeyup(ctx, e);
    const onWindowKeydown = (e) => trapFocus(ctx, e);
    const onWindowResize = () => requireRefresh(ctx);
    const onWindowScroll = () => requireRefresh(ctx);
    const onClick = (e) => onDocumentClick(ctx, e);
    ctx.setState("__events", {
      onKeyup: onWindowKeyup,
      onKeydown: onWindowKeydown,
      onResize: onWindowResize,
      onScroll: onWindowScroll,
      onClick
    });
    window.addEventListener("keyup", onWindowKeyup, false);
    window.addEventListener("keydown", onWindowKeydown, false);
    window.addEventListener("resize", onWindowResize);
    window.addEventListener("scroll", onWindowScroll);
    document.addEventListener("click", onClick, false);
  }
  function destroyEvents(ctx) {
    const events = ctx.getState("__events");
    if (!events) {
      return;
    }
    window.removeEventListener("keyup", events.onKeyup);
    window.removeEventListener("keydown", events.onKeydown);
    window.removeEventListener("resize", events.onResize);
    window.removeEventListener("scroll", events.onScroll);
    document.removeEventListener("click", events.onClick, false);
  }

  // src/context.ts
  function createConfigStore() {
    let currentConfig = {};
    function configure(config = {}) {
      currentConfig = {
        animate: true,
        duration: 400,
        allowClose: true,
        allowScroll: true,
        overlayClickBehavior: "close",
        overlayOpacity: 0.7,
        smoothScroll: false,
        disableActiveInteraction: false,
        advanceOnClick: false,
        skipMissingElement: false,
        waitForElement: 0,
        showProgress: false,
        stagePadding: 10,
        stageRadius: 5,
        popoverOffset: 10,
        showButtons: ["next", "previous", "close"],
        disableButtons: [],
        overlayColor: "#000",
        ...config
      };
    }
    const getConfig = ((key) => {
      return key ? currentConfig[key] : currentConfig;
    });
    configure();
    return { getConfig, configure };
  }
  function createStateStore() {
    let currentState = {};
    const getState = ((key) => {
      return key ? currentState[key] : currentState;
    });
    const setState = (key, value) => {
      currentState[key] = value;
    };
    function resetState() {
      currentState = {};
    }
    return { getState, setState, resetState };
  }
  function createEmitter() {
    let registeredListeners = {};
    function listen(hook, callback) {
      registeredListeners[hook] = callback;
    }
    function emit(hook) {
      registeredListeners[hook]?.();
    }
    function reset() {
      registeredListeners = {};
    }
    return { listen, emit, reset };
  }
  function createContext(options = {}) {
    const config = createConfigStore();
    config.configure(options);
    const state = createStateStore();
    const emitter = createEmitter();
    let driver2;
    return {
      getConfig: config.getConfig,
      setConfig: config.configure,
      getState: state.getState,
      setState: state.setState,
      resetState: state.resetState,
      listen: emitter.listen,
      emit: emitter.emit,
      resetEmitter: emitter.reset,
      getDriver: () => driver2,
      setDriver: (value) => {
        driver2 = value;
      },
      getHookOpts: (stateOverride) => {
        const activeState = stateOverride || state.getState();
        return {
          config: config.getConfig(),
          state: activeState,
          driver: driver2,
          index: activeState.activeIndex
        };
      }
    };
  }

  // src/driver.ts
  var teardownActive;
  function driver(options = {}) {
    const ctx = createContext(options);
    const teardown = () => destroy(false);
    function handleClose() {
      if (!ctx.getConfig("allowClose")) {
        return;
      }
      destroy();
    }
    function handleEscape() {
      const { disableButtons } = resolveStepButtons(ctx, ctx.getState("__activeStep"));
      if (disableButtons.includes("close")) {
        return;
      }
      handleClose();
    }
    function canUseButton(button) {
      const { showButtons, disableButtons } = resolveStepButtons(ctx, ctx.getState("__activeStep"));
      return showButtons.includes(button) && !disableButtons.includes(button);
    }
    function handleOverlayClick() {
      const overlayClickBehavior = ctx.getConfig("overlayClickBehavior");
      if (ctx.getConfig("allowClose") && overlayClickBehavior === "close") {
        destroy();
        return;
      }
      if (typeof overlayClickBehavior === "function") {
        const activeStep = ctx.getState("__activeStep");
        const activeElement = ctx.getState("__activeElement");
        overlayClickBehavior(activeElement, activeStep, ctx.getHookOpts());
        return;
      }
      if (overlayClickBehavior === "nextStep") {
        const activeStep = ctx.getState("activeStep");
        const activeElement = ctx.getState("activeElement");
        const onNextClick = resolveNextHook(ctx, activeStep);
        if (onNextClick) {
          onNextClick(activeElement, activeStep, ctx.getHookOpts());
          return;
        }
        moveNext();
      }
    }
    function moveNext() {
      const activeIndex = ctx.getState("activeIndex");
      const steps = ctx.getConfig("steps") || [];
      if (typeof activeIndex === "undefined") {
        return;
      }
      const nextStepIndex = activeIndex + 1;
      if (steps[nextStepIndex]) {
        drive(nextStepIndex);
      } else {
        destroy();
      }
    }
    function movePrevious() {
      const activeIndex = ctx.getState("activeIndex");
      const steps = ctx.getConfig("steps") || [];
      if (typeof activeIndex === "undefined") {
        return;
      }
      const previousStepIndex = activeIndex - 1;
      if (steps[previousStepIndex]) {
        drive(previousStepIndex);
      } else {
        destroy();
      }
    }
    function moveTo(index) {
      const steps = ctx.getConfig("steps") || [];
      if (steps[index]) {
        drive(index);
      } else {
        destroy();
      }
    }
    function handleActiveElementClick() {
      const isTransitioning = ctx.getState("__transitionCallback");
      if (isTransitioning) {
        return;
      }
      const activeStep = ctx.getState("__activeStep");
      if (!activeStep) {
        return;
      }
      const advanceOnClick = activeStep.advanceOnClick ?? ctx.getConfig("advanceOnClick");
      if (!advanceOnClick) {
        return;
      }
      const activeElement = ctx.getState("__activeElement");
      const onNextClick = resolveNextHook(ctx, activeStep);
      if (onNextClick) {
        onNextClick(activeElement, activeStep, ctx.getHookOpts());
        return;
      }
      moveNext();
    }
    function handleArrowLeft() {
      const isTransitioning = ctx.getState("__transitionCallback");
      if (isTransitioning) {
        return;
      }
      const activeIndex = ctx.getState("activeIndex");
      const activeStep = ctx.getState("__activeStep");
      const activeElement = ctx.getState("__activeElement");
      if (typeof activeIndex === "undefined" || typeof activeStep === "undefined") {
        return;
      }
      const steps = ctx.getConfig("steps") || [];
      if (!steps[activeIndex - 1]) {
        return;
      }
      if (!canUseButton("previous")) {
        return;
      }
      const onPrevClick = resolvePrevHook(ctx, activeStep);
      if (onPrevClick) {
        return onPrevClick(activeElement, activeStep, ctx.getHookOpts());
      }
      movePrevious();
    }
    function handleArrowRight() {
      const isTransitioning = ctx.getState("__transitionCallback");
      if (isTransitioning) {
        return;
      }
      const activeIndex = ctx.getState("activeIndex");
      const activeStep = ctx.getState("__activeStep");
      const activeElement = ctx.getState("__activeElement");
      if (typeof activeIndex === "undefined" || typeof activeStep === "undefined") {
        return;
      }
      if (!canUseButton("next")) {
        return;
      }
      const onNextClick = resolveNextHook(ctx, activeStep);
      if (onNextClick) {
        return onNextClick(activeElement, activeStep, ctx.getHookOpts());
      }
      moveNext();
    }
    function init() {
      if (ctx.getState("isInitialized")) {
        return;
      }
      if (teardownActive && teardownActive !== teardown) {
        teardownActive();
      }
      teardownActive = teardown;
      ctx.setState("isInitialized", true);
      document.body.classList.add("driver-active", ctx.getConfig("animate") ? "driver-fade" : "driver-simple");
      if (!ctx.getConfig("allowScroll")) {
        document.body.classList.add("driver-no-scroll");
      }
      document.body.style.setProperty("--driver-animation-duration", `${ctx.getConfig("duration") || 400}ms`);
      initEvents(ctx);
      ctx.listen("overlayClick", handleOverlayClick);
      ctx.listen("activeElementClick", handleActiveElementClick);
      ctx.listen("escapePress", handleEscape);
      ctx.listen("closeClick", handleClose);
      ctx.listen("arrowLeftPress", handleArrowLeft);
      ctx.listen("arrowRightPress", handleArrowRight);
    }
    function cancelElementWait() {
      const cancel = ctx.getState("__pendingWaitCancel");
      if (!cancel) {
        return;
      }
      ctx.setState("__pendingWaitCancel", void 0);
      cancel();
    }
    function waitForStepElement(step, timeout, onSettled) {
      const settle = () => {
        observer.disconnect();
        window.clearTimeout(timer);
        ctx.setState("__pendingWaitCancel", void 0);
        onSettled();
      };
      const observer = new MutationObserver(() => {
        if (resolveElement(step.element)) {
          settle();
        }
      });
      const timer = window.setTimeout(settle, timeout);
      ctx.setState("__pendingWaitCancel", () => {
        observer.disconnect();
        window.clearTimeout(timer);
      });
      observer.observe(document.documentElement, { childList: true, subtree: true, attributes: true });
    }
    function drive(stepIndex = 0, hasWaitedForElement = false) {
      cancelElementWait();
      const steps = ctx.getConfig("steps");
      if (!steps) {
        console.error("No steps to drive through");
        destroy();
        return;
      }
      if (!steps[stepIndex]) {
        destroy();
        return;
      }
      const currentStep = steps[stepIndex];
      const waitTimeout = currentStep.waitForElement ?? ctx.getConfig("waitForElement") ?? 0;
      if (!hasWaitedForElement && waitTimeout > 0 && currentStep.element && !resolveElement(currentStep.element)) {
        hidePopover(ctx.getState("popover"));
        waitForStepElement(currentStep, waitTimeout, () => drive(stepIndex, true));
        return;
      }
      if (shouldSkipStep(ctx, currentStep)) {
        const activeIndex = ctx.getState("activeIndex");
        const direction = typeof activeIndex === "number" && stepIndex < activeIndex ? -1 : 1;
        if (steps[stepIndex + direction]) {
          drive(stepIndex + direction);
        } else if (direction === 1) {
          destroy();
        }
        return;
      }
      ctx.setState("__activeOnDestroyed", document.activeElement);
      ctx.setState("activeIndex", stepIndex);
      const hasNextStep = steps[stepIndex + 1];
      highlight(
        ctx,
        resolveTourStep(ctx, stepIndex, {
          onNextClick: () => {
            if (!hasNextStep) {
              destroy();
            } else {
              drive(stepIndex + 1);
            }
          },
          onPrevClick: () => {
            drive(stepIndex - 1);
          },
          onCloseClick: () => {
            destroy();
          }
        })
      );
    }
    function destroy(withOnDestroyStartedHook = true) {
      if (!ctx.getState("isInitialized")) {
        return;
      }
      const activeElement = ctx.getState("__activeElement");
      const activeStep = ctx.getState("__activeStep");
      const activeOnDestroyed = ctx.getState("__activeOnDestroyed");
      const onDestroyStarted = ctx.getConfig("onDestroyStarted");
      if (withOnDestroyStartedHook && onDestroyStarted) {
        const isActiveDummyElement = !activeElement || activeElement?.id === "driver-dummy-element";
        onDestroyStarted(isActiveDummyElement ? void 0 : activeElement, activeStep, ctx.getHookOpts());
        return;
      }
      if (teardownActive === teardown) {
        teardownActive = void 0;
      }
      const onDeselected = activeStep?.onDeselected || ctx.getConfig("onDeselected");
      const onDestroyed = ctx.getConfig("onDestroyed");
      document.body.classList.remove("driver-active", "driver-fade", "driver-simple", "driver-no-scroll");
      document.body.style.removeProperty("--driver-animation-duration");
      cancelElementWait();
      destroyEvents(ctx);
      destroyPopover(ctx.getState("popover"));
      destroyHighlight();
      destroyOverlay(ctx);
      ctx.resetEmitter();
      const stateBeforeDestroy = ctx.getState();
      ctx.resetState();
      if (activeElement && activeStep) {
        const isActiveDummyElement = activeElement.id === "driver-dummy-element";
        if (onDeselected) {
          onDeselected(isActiveDummyElement ? void 0 : activeElement, activeStep, ctx.getHookOpts(stateBeforeDestroy));
        }
        if (onDestroyed) {
          onDestroyed(isActiveDummyElement ? void 0 : activeElement, activeStep, ctx.getHookOpts(stateBeforeDestroy));
        }
      }
      if (activeOnDestroyed) {
        activeOnDestroyed.focus();
      }
    }
    const api = {
      isActive: () => ctx.getState("isInitialized") || false,
      refresh: () => requireRefresh(ctx),
      drive: (stepIndex = 0) => {
        init();
        drive(stepIndex);
      },
      setConfig: ctx.setConfig,
      setSteps: (steps) => {
        cancelElementWait();
        ctx.resetState();
        ctx.setConfig({
          ...ctx.getConfig(),
          steps
        });
      },
      getConfig: ctx.getConfig,
      getState: ctx.getState,
      getActiveIndex: () => ctx.getState("activeIndex"),
      isFirstStep: () => {
        const activeIndex = ctx.getState("activeIndex");
        return activeIndex !== void 0 && findReachableIndex(ctx, activeIndex - 1, -1) === void 0;
      },
      isLastStep: () => {
        const activeIndex = ctx.getState("activeIndex");
        return activeIndex !== void 0 && findReachableIndex(ctx, activeIndex + 1, 1) === void 0;
      },
      getActiveStep: () => ctx.getState("activeStep"),
      getActiveElement: () => ctx.getState("activeElement"),
      getPreviousElement: () => ctx.getState("previousElement"),
      getPreviousStep: () => ctx.getState("previousStep"),
      getNextStep: () => {
        const steps = ctx.getConfig("steps") || [];
        const activeIndex = ctx.getState("activeIndex");
        if (activeIndex === void 0) {
          return void 0;
        }
        const nextIndex = findReachableIndex(ctx, activeIndex + 1, 1);
        return nextIndex !== void 0 ? steps[nextIndex] : void 0;
      },
      moveNext,
      movePrevious,
      moveTo,
      hasNextStep: () => {
        const activeIndex = ctx.getState("activeIndex");
        return activeIndex !== void 0 && findReachableIndex(ctx, activeIndex + 1, 1) !== void 0;
      },
      hasPreviousStep: () => {
        const activeIndex = ctx.getState("activeIndex");
        return activeIndex !== void 0 && findReachableIndex(ctx, activeIndex - 1, -1) !== void 0;
      },
      highlight: (step) => {
        init();
        highlight(ctx, {
          ...step,
          popover: step.popover ? {
            showButtons: [],
            showProgress: false,
            progressText: "",
            ...step.popover
          } : void 0
        });
      },
      destroy: () => {
        destroy(false);
      }
    };
    ctx.setDriver(api);
    return api;
  }
  return __toCommonJS(driver_exports);
})();
