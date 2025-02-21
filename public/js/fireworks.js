/**
 * Minified by jsDelivr using Terser v5.19.2.
 * Original file: /npm/canvas-confetti@1.9.3/dist/confetti.browser.js
 *
 * Do NOT use SRI with dynamically generated files! More information: https://www.jsdelivr.com/using-sri-with-dynamic-files
 */
!(function (t, e) {
	!(function t(e, a, n, r) {
		var o = !!(
				e.Worker &&
				e.Blob &&
				e.Promise &&
				e.OffscreenCanvas &&
				e.OffscreenCanvasRenderingContext2D &&
				e.HTMLCanvasElement &&
				e.HTMLCanvasElement.prototype.transferControlToOffscreen &&
				e.URL &&
				e.URL.createObjectURL
			),
			i = "function" == typeof Path2D && "function" == typeof DOMMatrix,
			l = (function () {
				if (!e.OffscreenCanvas) return !1;
				var t = new OffscreenCanvas(1, 1),
					a = t.getContext("2d");
				a.fillRect(0, 0, 1, 1);
				var n = t.transferToImageBitmap();
				try {
					a.createPattern(n, "no-repeat");
				} catch (t) {
					return !1;
				}
				return !0;
			})();
		function s() {}
		function c(t) {
			var n = a.exports.Promise,
				r = void 0 !== n ? n : e.Promise;
			return "function" == typeof r ? new r(t) : (t(s, s), null);
		}
		var h,
			f,
			u,
			d,
			m,
			g,
			p,
			b,
			M,
			v,
			y,
			w =
				((h = l),
				(f = new Map()),
				{
					transform: function (t) {
						if (h) return t;
						if (f.has(t)) return f.get(t);
						var e = new OffscreenCanvas(t.width, t.height);
						return e.getContext("2d").drawImage(t, 0, 0), f.set(t, e), e;
					},
					clear: function () {
						f.clear();
					},
				}),
			x =
				((m = Math.floor(1e3 / 60)),
				(g = {}),
				(p = 0),
				"function" == typeof requestAnimationFrame &&
				"function" == typeof cancelAnimationFrame
					? ((u = function (t) {
							var e = Math.random();
							return (
								(g[e] = requestAnimationFrame(function a(n) {
									p === n || p + m - 1 < n
										? ((p = n), delete g[e], t())
										: (g[e] = requestAnimationFrame(a));
								})),
								e
							);
					  }),
					  (d = function (t) {
							g[t] && cancelAnimationFrame(g[t]);
					  }))
					: ((u = function (t) {
							return setTimeout(t, m);
					  }),
					  (d = function (t) {
							return clearTimeout(t);
					  })),
				{ frame: u, cancel: d }),
			C =
				((v = {}),
				function () {
					if (b) return b;
					if (!n && o) {
						var e = [
							"var CONFETTI, SIZE = {}, module = {};",
							"(" + t.toString() + ")(this, module, true, SIZE);",
							"onmessage = function(msg) {",
							"  if (msg.data.options) {",
							"    CONFETTI(msg.data.options).then(function () {",
							"      if (msg.data.callback) {",
							"        postMessage({ callback: msg.data.callback });",
							"      }",
							"    });",
							"  } else if (msg.data.reset) {",
							"    CONFETTI && CONFETTI.reset();",
							"  } else if (msg.data.resize) {",
							"    SIZE.width = msg.data.resize.width;",
							"    SIZE.height = msg.data.resize.height;",
							"  } else if (msg.data.canvas) {",
							"    SIZE.width = msg.data.canvas.width;",
							"    SIZE.height = msg.data.canvas.height;",
							"    CONFETTI = module.exports.create(msg.data.canvas);",
							"  }",
							"}",
						].join("\n");
						try {
							b = new Worker(URL.createObjectURL(new Blob([e])));
						} catch (t) {
							return (
								void 0 !== typeof console &&
									"function" == typeof console.warn &&
									console.warn("🎊 Could not load worker", t),
								null
							);
						}
						!(function (t) {
							function e(e, a) {
								t.postMessage({ options: e || {}, callback: a });
							}
							(t.init = function (e) {
								var a = e.transferControlToOffscreen();
								t.postMessage({ canvas: a }, [a]);
							}),
								(t.fire = function (a, n, r) {
									if (M) return e(a, null), M;
									var o = Math.random().toString(36).slice(2);
									return (M = c(function (n) {
										function i(e) {
											e.data.callback === o &&
												(delete v[o],
												t.removeEventListener("message", i),
												(M = null),
												w.clear(),
												r(),
												n());
										}
										t.addEventListener("message", i),
											e(a, o),
											(v[o] = i.bind(null, { data: { callback: o } }));
									}));
								}),
								(t.reset = function () {
									for (var e in (t.postMessage({ reset: !0 }), v))
										v[e](), delete v[e];
								});
						})(b);
					}
					return b;
				}),
			I = {
				particleCount: 50,
				angle: 90,
				spread: 45,
				startVelocity: 45,
				decay: 0.9,
				gravity: 1,
				drift: 0,
				ticks: 200,
				x: 0.5,
				y: 0.5,
				shapes: ["square", "circle"],
				zIndex: 100,
				colors: [
					"#26ccff",
					"#a25afd",
					"#ff5e7e",
					"#88ff5a",
					"#fcff42",
					"#ffa62d",
					"#ff36ff",
				],
				disableForReducedMotion: !1,
				scalar: 1,
			};
		function T(t, e, a) {
			return (function (t, e) {
				return e ? e(t) : t;
			})(t && null != t[e] ? t[e] : I[e], a);
		}
		function E(t) {
			return t < 0 ? 0 : Math.floor(t);
		}
		function P(t) {
			return parseInt(t, 16);
		}
		function S(t) {
			return t.map(O);
		}
		function O(t) {
			var e = String(t).replace(/[^0-9a-f]/gi, "");
			return (
				e.length < 6 && (e = e[0] + e[0] + e[1] + e[1] + e[2] + e[2]),
				{
					r: P(e.substring(0, 2)),
					g: P(e.substring(2, 4)),
					b: P(e.substring(4, 6)),
				}
			);
		}
		function k(t) {
			(t.width = document.documentElement.clientWidth),
				(t.height = document.documentElement.clientHeight);
		}
		function B(t) {
			var e = t.getBoundingClientRect();
			(t.width = e.width), (t.height = e.height);
		}
		function F(t, e) {
			(e.x += Math.cos(e.angle2D) * e.velocity + e.drift),
				(e.y += Math.sin(e.angle2D) * e.velocity + e.gravity),
				(e.velocity *= e.decay),
				e.flat
					? ((e.wobble = 0),
					  (e.wobbleX = e.x + 10 * e.scalar),
					  (e.wobbleY = e.y + 10 * e.scalar),
					  (e.tiltSin = 0),
					  (e.tiltCos = 0),
					  (e.random = 1))
					: ((e.wobble += e.wobbleSpeed),
					  (e.wobbleX = e.x + 10 * e.scalar * Math.cos(e.wobble)),
					  (e.wobbleY = e.y + 10 * e.scalar * Math.sin(e.wobble)),
					  (e.tiltAngle += 0.1),
					  (e.tiltSin = Math.sin(e.tiltAngle)),
					  (e.tiltCos = Math.cos(e.tiltAngle)),
					  (e.random = Math.random() + 2));
			var a = e.tick++ / e.totalTicks,
				n = e.x + e.random * e.tiltCos,
				r = e.y + e.random * e.tiltSin,
				o = e.wobbleX + e.random * e.tiltCos,
				l = e.wobbleY + e.random * e.tiltSin;
			if (
				((t.fillStyle =
					"rgba(" +
					e.color.r +
					", " +
					e.color.g +
					", " +
					e.color.b +
					", " +
					(1 - a) +
					")"),
				t.beginPath(),
				i &&
					"path" === e.shape.type &&
					"string" == typeof e.shape.path &&
					Array.isArray(e.shape.matrix))
			)
				t.fill(
					(function (t, e, a, n, r, o, i) {
						var l = new Path2D(t),
							s = new Path2D();
						s.addPath(l, new DOMMatrix(e));
						var c = new Path2D();
						return (
							c.addPath(
								s,
								new DOMMatrix([
									Math.cos(i) * r,
									Math.sin(i) * r,
									-Math.sin(i) * o,
									Math.cos(i) * o,
									a,
									n,
								])
							),
							c
						);
					})(
						e.shape.path,
						e.shape.matrix,
						e.x,
						e.y,
						0.1 * Math.abs(o - n),
						0.1 * Math.abs(l - r),
						(Math.PI / 10) * e.wobble
					)
				);
			else if ("bitmap" === e.shape.type) {
				var s = (Math.PI / 10) * e.wobble,
					c = 0.1 * Math.abs(o - n),
					h = 0.1 * Math.abs(l - r),
					f = e.shape.bitmap.width * e.scalar,
					u = e.shape.bitmap.height * e.scalar,
					d = new DOMMatrix([
						Math.cos(s) * c,
						Math.sin(s) * c,
						-Math.sin(s) * h,
						Math.cos(s) * h,
						e.x,
						e.y,
					]);
				d.multiplySelf(new DOMMatrix(e.shape.matrix));
				var m = t.createPattern(w.transform(e.shape.bitmap), "no-repeat");
				m.setTransform(d),
					(t.globalAlpha = 1 - a),
					(t.fillStyle = m),
					t.fillRect(e.x - f / 2, e.y - u / 2, f, u),
					(t.globalAlpha = 1);
			} else if ("circle" === e.shape)
				t.ellipse
					? t.ellipse(
							e.x,
							e.y,
							Math.abs(o - n) * e.ovalScalar,
							Math.abs(l - r) * e.ovalScalar,
							(Math.PI / 10) * e.wobble,
							0,
							2 * Math.PI
					  )
					: (function (t, e, a, n, r, o, i, l, s) {
							t.save(),
								t.translate(e, a),
								t.rotate(o),
								t.scale(n, r),
								t.arc(0, 0, 1, i, l, s),
								t.restore();
					  })(
							t,
							e.x,
							e.y,
							Math.abs(o - n) * e.ovalScalar,
							Math.abs(l - r) * e.ovalScalar,
							(Math.PI / 10) * e.wobble,
							0,
							2 * Math.PI
					  );
			else if ("star" === e.shape)
				for (
					var g = (Math.PI / 2) * 3,
						p = 4 * e.scalar,
						b = 8 * e.scalar,
						M = e.x,
						v = e.y,
						y = 5,
						x = Math.PI / y;
					y--;

				)
					(M = e.x + Math.cos(g) * b),
						(v = e.y + Math.sin(g) * b),
						t.lineTo(M, v),
						(g += x),
						(M = e.x + Math.cos(g) * p),
						(v = e.y + Math.sin(g) * p),
						t.lineTo(M, v),
						(g += x);
			else
				t.moveTo(Math.floor(e.x), Math.floor(e.y)),
					t.lineTo(Math.floor(e.wobbleX), Math.floor(r)),
					t.lineTo(Math.floor(o), Math.floor(l)),
					t.lineTo(Math.floor(n), Math.floor(e.wobbleY));
			return t.closePath(), t.fill(), e.tick < e.totalTicks;
		}
		function A(t, a) {
			var i,
				l = !t,
				s = !!T(a || {}, "resize"),
				h = !1,
				f = T(a, "disableForReducedMotion", Boolean),
				u = o && !!T(a || {}, "useWorker") ? C() : null,
				d = l ? k : B,
				m = !(!t || !u) && !!t.__confetti_initialized,
				g =
					"function" == typeof matchMedia &&
					matchMedia("(prefers-reduced-motion)").matches;
			function p(e, a, o) {
				for (
					var l,
						s,
						h,
						f,
						u,
						m = T(e, "particleCount", E),
						g = T(e, "angle", Number),
						p = T(e, "spread", Number),
						b = T(e, "startVelocity", Number),
						M = T(e, "decay", Number),
						v = T(e, "gravity", Number),
						y = T(e, "drift", Number),
						C = T(e, "colors", S),
						I = T(e, "ticks", Number),
						P = T(e, "shapes"),
						O = T(e, "scalar"),
						k = !!T(e, "flat"),
						B = (function (t) {
							var e = T(t, "origin", Object);
							return (e.x = T(e, "x", Number)), (e.y = T(e, "y", Number)), e;
						})(e),
						A = m,
						R = [],
						N = t.width * B.x,
						z = t.height * B.y;
					A--;

				)
					R.push(
						((l = {
							x: N,
							y: z,
							angle: g,
							spread: p,
							startVelocity: b,
							color: C[A % C.length],
							shape:
								P[
									((f = 0),
									(u = P.length),
									Math.floor(Math.random() * (u - f)) + f)
								],
							ticks: I,
							decay: M,
							gravity: v,
							drift: y,
							scalar: O,
							flat: k,
						}),
						(s = void 0),
						(h = void 0),
						(s = l.angle * (Math.PI / 180)),
						(h = l.spread * (Math.PI / 180)),
						{
							x: l.x,
							y: l.y,
							wobble: 10 * Math.random(),
							wobbleSpeed: Math.min(0.11, 0.1 * Math.random() + 0.05),
							velocity: 0.5 * l.startVelocity + Math.random() * l.startVelocity,
							angle2D: -s + (0.5 * h - Math.random() * h),
							tiltAngle: (0.5 * Math.random() + 0.25) * Math.PI,
							color: l.color,
							shape: l.shape,
							tick: 0,
							totalTicks: l.ticks,
							decay: l.decay,
							drift: l.drift,
							random: Math.random() + 2,
							tiltSin: 0,
							tiltCos: 0,
							wobbleX: 0,
							wobbleY: 0,
							gravity: 3 * l.gravity,
							ovalScalar: 0.6,
							scalar: l.scalar,
							flat: l.flat,
						})
					);
				return i
					? i.addFettis(R)
					: ((i = (function (t, e, a, o, i) {
							var l,
								s,
								h = e.slice(),
								f = t.getContext("2d"),
								u = c(function (e) {
									function c() {
										(l = s = null),
											f.clearRect(0, 0, o.width, o.height),
											w.clear(),
											i(),
											e();
									}
									(l = x.frame(function e() {
										!n ||
											(o.width === r.width && o.height === r.height) ||
											((o.width = t.width = r.width),
											(o.height = t.height = r.height)),
											o.width ||
												o.height ||
												(a(t), (o.width = t.width), (o.height = t.height)),
											f.clearRect(0, 0, o.width, o.height),
											(h = h.filter(function (t) {
												return F(f, t);
											})).length
												? (l = x.frame(e))
												: c();
									})),
										(s = c);
								});
							return {
								addFettis: function (t) {
									return (h = h.concat(t)), u;
								},
								canvas: t,
								promise: u,
								reset: function () {
									l && x.cancel(l), s && s();
								},
							};
					  })(t, R, d, a, o)),
					  i.promise);
			}
			function b(a) {
				var n = f || T(a, "disableForReducedMotion", Boolean),
					r = T(a, "zIndex", Number);
				if (n && g)
					return c(function (t) {
						t();
					});
				l && i
					? (t = i.canvas)
					: l &&
					  !t &&
					  ((t = (function (t) {
							var e = document.createElement("canvas");
							return (
								(e.style.position = "fixed"),
								(e.style.top = "0px"),
								(e.style.left = "0px"),
								(e.style.pointerEvents = "none"),
								(e.style.zIndex = t),
								e
							);
					  })(r)),
					  document.body.appendChild(t)),
					s && !m && d(t);
				var o = { width: t.width, height: t.height };
				function b() {
					if (u) {
						var e = {
							getBoundingClientRect: function () {
								if (!l) return t.getBoundingClientRect();
							},
						};
						return (
							d(e),
							void u.postMessage({
								resize: { width: e.width, height: e.height },
							})
						);
					}
					o.width = o.height = null;
				}
				function M() {
					(i = null),
						s && ((h = !1), e.removeEventListener("resize", b)),
						l &&
							t &&
							(document.body.contains(t) && document.body.removeChild(t),
							(t = null),
							(m = !1));
				}
				return (
					u && !m && u.init(t),
					(m = !0),
					u && (t.__confetti_initialized = !0),
					s && !h && ((h = !0), e.addEventListener("resize", b, !1)),
					u ? u.fire(a, o, M) : p(a, o, M)
				);
			}
			return (
				(b.reset = function () {
					u && u.reset(), i && i.reset();
				}),
				b
			);
		}
		function R() {
			return y || (y = A(null, { useWorker: !0, resize: !0 })), y;
		}
		(a.exports = function () {
			return R().apply(this, arguments);
		}),
			(a.exports.reset = function () {
				R().reset();
			}),
			(a.exports.create = A),
			(a.exports.shapeFromPath = function (t) {
				if (!i)
					throw new Error("path confetti are not supported in this browser");
				var e, a;
				"string" == typeof t ? (e = t) : ((e = t.path), (a = t.matrix));
				var n = new Path2D(e),
					r = document.createElement("canvas").getContext("2d");
				if (!a) {
					for (
						var o, l, s = 1e3, c = s, h = s, f = 0, u = 0, d = 0;
						d < s;
						d += 2
					)
						for (var m = 0; m < s; m += 2)
							r.isPointInPath(n, d, m, "nonzero") &&
								((c = Math.min(c, d)),
								(h = Math.min(h, m)),
								(f = Math.max(f, d)),
								(u = Math.max(u, m)));
					(o = f - c), (l = u - h);
					var g = Math.min(10 / o, 10 / l);
					a = [
						g,
						0,
						0,
						g,
						-Math.round(o / 2 + c) * g,
						-Math.round(l / 2 + h) * g,
					];
				}
				return { type: "path", path: e, matrix: a };
			}),
			(a.exports.shapeFromText = function (t) {
				var e,
					a = 1,
					n = "#000000",
					r =
						'"Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji", "EmojiOne Color", "Android Emoji", "Twemoji Mozilla", "system emoji", sans-serif';
				"string" == typeof t
					? (e = t)
					: ((e = t.text),
					  (a = "scalar" in t ? t.scalar : a),
					  (r = "fontFamily" in t ? t.fontFamily : r),
					  (n = "color" in t ? t.color : n));
				var o = 10 * a,
					i = o + "px " + r,
					l = new OffscreenCanvas(o, o),
					s = l.getContext("2d");
				s.font = i;
				var c = s.measureText(e),
					h = Math.ceil(c.actualBoundingBoxRight + c.actualBoundingBoxLeft),
					f = Math.ceil(c.actualBoundingBoxAscent + c.actualBoundingBoxDescent),
					u = c.actualBoundingBoxLeft + 2,
					d = c.actualBoundingBoxAscent + 2;
				(h += 4),
					(f += 4),
					((s = (l = new OffscreenCanvas(h, f)).getContext("2d")).font = i),
					(s.fillStyle = n),
					s.fillText(e, u, d);
				var m = 1 / a;
				return {
					type: "bitmap",
					bitmap: l.transferToImageBitmap(),
					matrix: [m, 0, 0, m, (-h * m) / 2, (-f * m) / 2],
				};
			});
	})(
		(function () {
			return void 0 !== t ? t : "undefined" != typeof self ? self : this || {};
		})(),
		e,
		!1
	),
		(t.confetti = e.exports);
})(window, {});
//# sourceMappingURL=/sm/0e9ac22f62a550282b886b288da51d7892173a94bbd286c2ffc6e7b881509c88.map
