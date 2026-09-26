# Finvo — Tài liệu dự án

Tài liệu nghiệp vụ và kế hoạch triển khai, chia theo ba tầng: **phase → feature → task**.

```text
docs/
├── phases/     Kế hoạch triển khai của từng phase (phạm vi, schema, business rule, thứ tự làm)
├── features/   Đặc tả chức năng của từng phase (người dùng làm được gì)
└── tasks/      Task chi tiết, gom theo chức năng (mỗi chức năng một thư mục, tách backend/frontend)
```

## Mục lục

### Phases

| Phase   | Kế hoạch                               |
| ------- | -------------------------------------- |
| Phase 1 | [phases/phase-1.md](phases/phase-1.md) |

### Features

| Phase   | Chức năng                                  |
| ------- | ------------------------------------------ |
| Phase 1 | [features/phase-1.md](features/phase-1.md) |

### Tasks

| Chức năng  | Phase | Task                                     | Trạng thái                                           |
| ---------- | ----- | ---------------------------------------- | ---------------------------------------------------- |
| Accounts   | 1     | [Backend](tasks/accounts/backend.md)     | ✅ Đã merge (PR #3, #4, #9, #10, #12, #14)           |
| Accounts   | 1     | [Frontend](tasks/accounts/frontend.md)   | ✅ Đã merge (PR #3, #4, #17)                         |
| Categories | 1     | [Backend](tasks/categories/backend.md)   | ✅ Đã merge (PR #18)                                 |
| Categories | 1     | [Frontend](tasks/categories/frontend.md) | ✅ Đã merge (PR #20)                                 |
| Multi-lang | 1     | [Backend](tasks/multi-lang/backend.md)   | ✅ Đã làm, chưa commit — `feat/implement-multi-lang` |
| Multi-lang | 1     | [Frontend](tasks/multi-lang/frontend.md) | ✅ Đã làm, chưa commit — `feat/implement-multi-lang` |

## Quy ước đặt tên

- Phase: `phases/phase-<n>.md`
- Feature: `features/phase-<n>.md`
- Task: `tasks/<chức-năng>/<backend|frontend>.md` — ví dụ `tasks/wallets/backend.md`. Ghi rõ phase trong phần đầu file.
- Tham chiếu giữa các tài liệu dùng link tương đối; tham chiếu từ code dùng đường dẫn từ gốc repo, ví dụ `docs/phases/phase-1.md §4.2`.
