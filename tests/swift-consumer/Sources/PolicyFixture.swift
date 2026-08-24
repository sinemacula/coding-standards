import Foundation

struct PolicyFixture: Sendable {
    static let exampleNames = [
        "Primary",
        "Secondary"
    ]

    let id: UUID
    let name: String

    var displayName: String {
        name.isEmpty ? "Unnamed" : name
    }
}
