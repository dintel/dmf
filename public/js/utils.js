function userTypeText(type) {
  if (type == "admin") {
    return "Administrator";
  } else if (type == "user") {
    return "User";
  }
  return "Unknown type";
}
